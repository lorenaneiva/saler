<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::query()->firstOrCreate(['name' => 'Bebidas']);

        Product::query()->firstOrCreate(
            ['name' => 'Água mineral 500 ml', 'category_id' => $category->id],
            ['sale_price' => '3.50'],
        );
    }
}
