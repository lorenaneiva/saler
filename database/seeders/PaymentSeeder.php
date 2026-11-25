<?php

namespace Database\Seeders;

use App\Actions\RegisterCustomerPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(RegisterCustomerPayment $registerPayment): void
    {
        $sale = Sale::query()->where('status', Sale::Completed)->withPaidTotal()->orderBy('sold_on')->orderBy('id')->first();

        if (! $sale || $sale->outstandingAmount()->isZero()) {
            return;
        }

        $registerPayment->handle($sale->customer, User::query()->findOrFail($sale->user_id), [
            'submission_token' => (string) Str::uuid(),
            'amount' => (string) $sale->outstandingAmount(),
            'paid_on' => now()->toDateString(),
            'method' => 'pix',
        ]);
    }
}
