<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([SupplierSeeder::class, ProductSeeder::class]);

        $supplier = Supplier::query()->where('name', 'Distribuidora de exemplo')->firstOrFail();
        $product = Product::query()->where('name', 'Água mineral 500 ml')->firstOrFail();
        $user = User::query()->first() ?? User::factory()->create();

        DB::transaction(function () use ($supplier, $product, $user): void {
            $purchase = Purchase::query()->firstOrCreate([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'purchased_on' => now()->toDateString(),
                'total' => '15.00',
            ]);

            $purchase->items()->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 10, 'unit_cost' => '1.50', 'discount' => '0.00', 'total' => '15.00'],
            );
        });
    }
}
