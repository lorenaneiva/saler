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
    /** @param array{submission_token: string, amount: int|float|numeric-string, paid_on: string, method: string, notes?: ?string} $data */
    public function handle(Customer $customer, User $user, array $data): void
    {
        DB::transaction(function () use ($customer, $user, $data): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if ($customer->payments()->where('submission_token', $data['submission_token'])->exists()) {
                return;
            }

            $sales = $customer->sales()->where('status', Sale::Completed)->withPaidTotal()
                ->orderBy('sold_on')->orderBy('id')->lockForUpdate()->get();

            $remaining = BigDecimal::of($data['amount']);
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

            $payment = $customer->payments()->create([...$data, 'user_id' => $user->id]);
            $payment->allocations()->createMany($allocations);
        }, 3);
    }
}
