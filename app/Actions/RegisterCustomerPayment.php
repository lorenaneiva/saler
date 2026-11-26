<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterCustomerPayment
{
    /**
     * Registers a customer payment, allocating it to the oldest open sales or exclusively to the given sale.
     *
     * @param  array{submission_token: string, amount: int|float|numeric-string, paid_on: string, method: string, notes?: ?string}  $data
     */
    public function handle(Customer $customer, User $user, array $data, ?Sale $targetSale = null): void
    {
        DB::transaction(function () use ($customer, $user, $data, $targetSale): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if ($customer->payments()->where('submission_token', $data['submission_token'])->exists()) {
                return;
            }

            $amount = BigDecimal::of($data['amount']);
            $allocations = $targetSale === null
                ? $this->allocateToOldestSales($customer, $amount)
                : $this->allocateToSale($customer, $targetSale, $amount);

            $payment = $customer->payments()->create([...$data, 'user_id' => $user->id]);
            $payment->allocations()->createMany($allocations);
        }, 3);
    }

    /** @return list<array{sale_id: int, amount: string}> */
    private function allocateToOldestSales(Customer $customer, BigDecimal $amount): array
    {
        $sales = $customer->sales()->where('status', Sale::Completed)->withPaidTotal()
            ->orderBy('sold_on')->orderBy('id')->lockForUpdate()->get();

        $remaining = $amount;
        $allocations = [];

        foreach ($sales as $sale) {
            $balance = $sale->outstandingAmount();

            if ($balance->isZero()) {
                continue;
            }

            $applied = BigDecimal::min($remaining, $balance);
            $allocations[] = ['sale_id' => $sale->id, 'amount' => (string) $applied];
            $remaining = $remaining->minus($applied);

            if ($remaining->isZero()) {
                break;
            }
        }

        if (! $remaining->isZero()) {
            throw ValidationException::withMessages(['amount' => 'O pagamento não pode ultrapassar o saldo em aberto do cliente.']);
        }

        return $allocations;
    }

    /** @return list<array{sale_id: int, amount: string}> */
    private function allocateToSale(Customer $customer, Sale $targetSale, BigDecimal $amount): array
    {
        $sale = $customer->sales()->whereKey($targetSale->id)->withPaidTotal()->lockForUpdate()->firstOrFail();

        if ($sale->status !== Sale::Completed) {
            throw ValidationException::withMessages(['amount' => 'Vendas canceladas não recebem pagamentos.']);
        }

        $balance = $sale->outstandingAmount();

        if ($balance->isZero()) {
            throw ValidationException::withMessages(['amount' => 'Esta venda já está quitada.']);
        }

        if ($amount->isGreaterThan($balance)) {
            throw ValidationException::withMessages(['amount' => 'O pagamento não pode ultrapassar o saldo em aberto desta venda.']);
        }

        return [['sale_id' => $sale->id, 'amount' => (string) $amount]];
    }
}
