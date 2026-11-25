<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Alimentos', 'Bebidas', 'Limpeza'] as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
