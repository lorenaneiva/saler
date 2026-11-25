<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::query()->firstOrCreate(
            ['email' => 'cliente@example.test'],
            ['name' => 'Cliente de exemplo', 'phone' => '91900000000'],
        );
    }
}
