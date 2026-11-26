<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterCustomerPayment
{
    public function __construct(private PlanPaymentDistribution $planDistribution) {}

    /**
     * Registers a customer payment, spreading it over the open sales with the smallest balances or exclusively to the given sale.
     *
     * @param  array{submission_token: string, amount: int|float|numeric-string, paid_on: string, method: string, notes?: ?string, distribution_signature?: ?string}  $data
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
                ? $this->allocateBySmallestBalance($customer, $amount, $data['distribution_signature'] ?? null)
                : $this->allocateToSale($customer, $targetSale, $amount);

            $payment = $customer->payments()->create([...Arr::except($data, 'distribution_signature'), 'user_id' => $user->id]);
            $payment->allocations()->createMany($allocations);
        }, 3);
    }

    /**
     * @param  ?string  $expectedSignature  Signature of the distribution previewed to the user, when there was one.
     * @return list<array{sale_id: int, amount: string}>
     */
    private function allocateBySmallestBalance(Customer $customer, BigDecimal $amount, ?string $expectedSignature): array
    {
        $distribution = $this->planDistribution->handle($customer, $amount, lockSales: true);

        if (! $distribution['unallocated']->isZero()) {
            throw ValidationException::withMessages(['amount' => 'O pagamento não pode ultrapassar o saldo em aberto do cliente.']);
        }

        if ($expectedSignature !== null && ! hash_equals($distribution['signature'], $expectedSignature)) {
            throw ValidationException::withMessages(['distribution_signature' => 'Os saldos das compras foram alterados por outra operação. Revise a nova distribuição e confirme o pagamento novamente.']);
        }

        return array_map(fn (array $line): array => ['sale_id' => $line['sale']->id, 'amount' => (string) $line['applied']], $distribution['lines']);
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
