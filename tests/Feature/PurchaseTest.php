<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_or_confirm_purchases(): void
    {
        $this->get(route('purchases.index'))->assertRedirectToRoute('login');
        $this->post(route('purchases.store'))->assertRedirectToRoute('login');
        $this->post(route('purchases.confirm', 1))->assertRedirectToRoute('login');
        $this->post(route('purchases.cancel', 1))->assertRedirectToRoute('login');
    }

    public function test_registering_a_purchase_preserves_costs_and_updates_stock_only_once_on_confirmation(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'Distribuidora Norte']);
        $rice = Product::factory()->create(['name' => 'Arroz', 'stock_quantity' => 2, 'average_cost' => '10.0000']);
        $beans = Product::factory()->create(['name' => 'Feijão']);

        $this->actingAs($user)->get(route('purchases.create'))->assertOk()
            ->assertSee('<select name="supplier_id"', false)
            ->assertSee('data-item-field="product_id"', false)
            ->assertSee('Distribuidora Norte')->assertSee('Arroz');

        $response = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_on' => '2026-11-24',
            'items' => [
                ['product_id' => $rice->id, 'quantity' => 3, 'unit_cost' => '20.00', 'discount' => '6.00'],
                ['product_id' => $beans->id, 'quantity' => 2, 'unit_cost' => '7.25'],
            ],
        ]);
        $purchase = Purchase::query()->sole();

        $response->assertRedirectToRoute('purchases.show', $purchase);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id, 'status' => Purchase::Pending, 'total' => '68.50', 'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id, 'product_id' => $rice->id, 'unit_cost' => '20.00', 'discount' => '6.00', 'total' => '54.00',
        ]);
        $this->assertDatabaseHas('products', ['id' => $rice->id, 'stock_quantity' => 2]);
        $this->assertDatabaseEmpty('stock_movements');
        $this->get(route('purchases.index'))->assertOk()->assertSee('Distribuidora Norte');
        $this->get(route('purchases.show', $purchase))->assertOk()->assertSee('R$ 68,50')->assertSee('Pendente');

        $this->post(route('purchases.confirm', $purchase))->assertRedirectToRoute('purchases.show', $purchase);
        $this->post(route('purchases.confirm', $purchase))->assertRedirectToRoute('purchases.show', $purchase);

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => Purchase::Confirmed]);
        $this->assertDatabaseHas('products', ['id' => $rice->id, 'stock_quantity' => 5, 'average_cost' => '14.8000']);
        $this->assertDatabaseHas('products', ['id' => $beans->id, 'stock_quantity' => 2, 'average_cost' => '7.2500']);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $rice->id, 'quantity_delta' => 3, 'unit_cost' => '18.0000', 'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->post(route('purchases.cancel', $purchase))->assertSessionHasErrors('purchase');
    }

    public function test_cancelling_a_pending_purchase_prevents_confirmation_and_stock_entry(): void
    {
        $purchase = Purchase::factory()->create();
        $product = Product::factory()->create();

        $purchase->items()->create([
            'product_id' => $product->id, 'quantity' => 5, 'unit_cost' => '10.00', 'discount' => '0.00', 'total' => '50.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('purchases.cancel', $purchase))->assertRedirectToRoute('purchases.show', $purchase);
        $this->from(route('purchases.show', $purchase))
            ->post(route('purchases.confirm', $purchase))
            ->assertRedirectToRoute('purchases.show', $purchase)
            ->assertSessionHasErrors('purchase');

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => Purchase::Cancelled]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_excessive_discounts_do_not_create_a_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('purchases.create'))
            ->followingRedirects()
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchased_on' => '2026-11-24',
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => '5.00', 'discount' => '10.01']],
            ])->assertOk()
            ->assertSee('O desconto não pode ultrapassar o subtotal do item.')
            ->assertSee('value="10.01"', false);
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('purchase_items');
    }
}
