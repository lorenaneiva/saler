<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Sale;
use Brick\Math\BigDecimal;

/**
 * Plans how a general customer payment is spread over open sales: smallest current balance first,
 * ties broken by the oldest sale and then by id. Used by both the preview and the registration.
 */
class PlanPaymentDistribution
{
    /**
     * @return array{
     *     lines: list<array{sale: Sale, balance: BigDecimal, applied: BigDecimal, remaining: BigDecimal}>,
     *     amount: BigDecimal,
     *     outstanding: BigDecimal,
     *     unallocated: BigDecimal,
     *     signature: string
     * }
     */
    public function handle(Customer $customer, BigDecimal $amount, bool $lockSales = false): array
    {
        $sales = $customer->sales()->where('status', Sale::Completed)->withPaidTotal()
            ->when($lockSales, fn ($query) => $query->lockForUpdate())
            ->get()
            ->map(fn (Sale $sale): array => ['sale' => $sale, 'balance' => $sale->outstandingAmount()])
            ->filter(fn (array $open): bool => $open['balance']->isPositive())
            ->sort(fn (array $first, array $second): int => $first['balance']->compareTo($second['balance'])
                ?: $first['sale']->sold_on <=> $second['sale']->sold_on
                ?: $first['sale']->id <=> $second['sale']->id)
            ->values();

        $outstanding = $sales->reduce(fn (BigDecimal $total, array $open): BigDecimal => $total->plus($open['balance']), BigDecimal::zero()->toScale(2));
        $remainingAmount = $amount->toScale(2);
        $lines = [];

        foreach ($sales as $open) {
            if (! $remainingAmount->isPositive()) {
                break;
            }

            $applied = BigDecimal::min($remainingAmount, $open['balance']);
            $lines[] = [...$open, 'applied' => $applied, 'remaining' => $open['balance']->minus($applied)];
            $remainingAmount = $remainingAmount->minus($applied);
        }

        return [
            'lines' => $lines,
            'amount' => $amount->toScale(2),
            'outstanding' => $outstanding,
            'unallocated' => $remainingAmount,
            'signature' => sha1(implode('|', array_map(fn (array $line): string => $line['sale']->id.':'.$line['applied'], $lines))),
        ];
    }
}
