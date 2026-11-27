<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_consult_or_register_sales(): void
    {
        $this->get(route('sales.index'))->assertRedirectToRoute('login');
        $this->get(route('sales.create'))->assertRedirectToRoute('login');
        $this->get(route('sales.show', 1))->assertRedirectToRoute('login');
        $this->post(route('sales.store'))->assertRedirectToRoute('login');
    }

    public function test_sale_deducts_stock_and_preserves_catalog_price_and_cost_at_registration(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Maria Silva']);
        $rice = Product::factory()->create(['name' => 'Arroz', 'stock_quantity' => 5, 'sale_price' => '12.50', 'average_cost' => '8.1250']);
        $beans = Product::factory()->create(['name' => 'Feijão', 'stock_quantity' => 1, 'sale_price' => '8.00', 'average_cost' => '5.0000']);

        $this->actingAs($user)->get(route('sales.create'))->assertOk()
            ->assertSee('Maria Silva')->assertSee('Arroz')->assertSee('5 em estoque');

        $response = $this->post(route('sales.store'), [
            'customer_id' => $customer->id,
            'sold_on' => '2026-11-24',
            'discount' => '1.25',
            'notes' => 'Retirada no balcão',
            'items' => [
                ['product_id' => $rice->id, 'quantity' => 2],
                ['product_id' => $beans->id, 'quantity' => 1],
            ],
        ]);
        $sale = Sale::query()->sole();

        $response->assertRedirectToRoute('sales.show', $sale);
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id, 'user_id' => $user->id, 'customer_id' => $customer->id,
            'subtotal' => '33.00', 'discount' => '1.25', 'total' => '31.75', 'status' => 'completed',
        ]);
        $item = $sale->items()->where('product_id', $rice->id)->sole();
        $this->assertDatabaseHas('sale_items', [
            'id' => $item->id, 'quantity' => 2, 'unit_price' => '12.50', 'unit_cost' => '8.1250', 'subtotal' => '25.00',
        ]);
        $this->assertDatabaseHas('products', ['id' => $rice->id, 'stock_quantity' => 3, 'average_cost' => '8.1250']);
        $this->assertDatabaseHas('products', ['id' => $beans->id, 'stock_quantity' => 0]);
        $this->assertDatabaseHas('stock_movements', [
            'sale_item_id' => $item->id, 'type' => StockMovement::SaleExit, 'quantity_delta' => -2, 'unit_cost' => '8.1250',
        ]);
        $this->assertDatabaseCount('stock_movements', 2);

        $rice->forceFill(['sale_price' => '15.00', 'average_cost' => '9.0000'])->save();

        $this->get(route('sales.index'))->assertOk()->assertSee('Maria Silva');
        $this->get(route('sales.show', $sale))->assertOk()
            ->assertSee('R$ 31,75')->assertSee('R$ 12,50')->assertDontSee('R$ 8,13')
            ->assertSee('Retirada no balcão');
        $this->get(route('products.show', $rice))->assertOk()
            ->assertSee(route('sales.show', $sale))->assertDontSee('Perda de estoque');
    }

    public function test_insufficient_stock_rejects_the_entire_sale(): void
    {
        $customer = Customer::factory()->create();
        $rice = Product::factory()->create(['stock_quantity' => 5]);
        $beans = Product::factory()->create(['name' => 'Feijão', 'stock_quantity' => 1]);

        $this->actingAs(User::factory()->create())
            ->from(route('sales.create'))
            ->post(route('sales.store'), [
                'customer_id' => $customer->id,
                'sold_on' => '2026-11-24',
                'items' => [
                    ['product_id' => $rice->id, 'quantity' => 2],
                    ['product_id' => $beans->id, 'quantity' => 2],
                ],
            ])->assertRedirectToRoute('sales.create')
            ->assertSessionHasErrors(['items.1.quantity' => 'Estoque insuficiente para vender Feijão.']);

        $this->assertDatabaseHas('products', ['id' => $rice->id, 'stock_quantity' => 5]);
        $this->assertDatabaseHas('products', ['id' => $beans->id, 'stock_quantity' => 1]);
        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseEmpty('sale_items');
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_discount_above_subtotal_does_not_register_a_sale(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['sale_price' => '10.00', 'stock_quantity' => 1]);

        $this->actingAs(User::factory()->create())
            ->from(route('sales.create'))
            ->followingRedirects()
            ->post(route('sales.store'), [
                'customer_id' => $customer->id,
                'sold_on' => '2026-11-24',
                'discount' => '10.01',
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
            ])->assertOk()
            ->assertSee('O desconto não pode ultrapassar o subtotal da venda.')
            ->assertSee('value="10.01"', false);

        $this->assertDatabaseEmpty('sales');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 1]);
    }
}
