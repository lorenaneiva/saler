<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Purchase> */
class PurchaseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'purchased_on' => now()->toDateString(),
        ];
    }
}
