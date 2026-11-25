<?php

namespace Database\Seeders;

use App\Actions\RegisterSale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(RegisterSale $registerSale): void
    {
        $this->call(CustomerSeeder::class);

        $customer = Customer::query()->where('email', 'cliente@example.test')->firstOrFail();
        $user = User::query()->orderBy('id')->first() ?? User::factory()->create();
        $product = Product::factory()->create(['name' => 'Produto de exemplo para venda', 'stock_quantity' => 5]);

        $registerSale->handle($user, [
            'customer_id' => $customer->id,
            'sold_on' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
    }
}
