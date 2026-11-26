<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MoneyFormattingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_formats_four_decimal_average_cost_as_brazilian_currency(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create([
            'name' => 'Arroz', 'sale_price' => '1250.50', 'average_cost' => '1250.5000',
        ]);

        $this->actingAs(User::factory()->create())->get(route('products.index'))
            ->assertOk()->assertSee('R$ 1.250,50')
            ->assertSeeTextInOrder(['R$ 1.250,50', 'R$ 1.250,50'])
            ->assertDontSee('R$ 1.250,5000');
    }

    public function test_shared_money_component_formats_integer_single_fractional_and_four_decimal_values(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            ['200.0000', 'R$ 200,00'],
            ['1250.5000', 'R$ 1.250,50'],
            ['15.5', 'R$ 15,50'],
            ['0', 'R$ 0,00'],
            ['-1250.5000', 'R$ -1.250,50'],
            [BigDecimal::of('1250.50'), 'R$ 1.250,50'],
        ] as [$value, $formatted]) {
            $rendered = view('components.ui.money', ['value' => $value])->render();

            $this->assertStringContainsString($formatted, $rendered);
        }
    }
}
