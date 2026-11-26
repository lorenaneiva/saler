<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(2, true),
            'sale_price' => '15.00',
        ];
    }

    /** Records a purchase entry so the product's average cost counts as registered. */
    public function withRegisteredCost(): static
    {
        return $this->afterCreating(function (Product $product): void {
            StockMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => User::factory()->create()->id,
                'quantity_delta' => max(1, $product->stock_quantity),
                'unit_cost' => $product->average_cost,
                'type' => StockMovement::PurchaseEntry,
            ]);
        });
    }
}
