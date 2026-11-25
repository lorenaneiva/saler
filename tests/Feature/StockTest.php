<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_consult_or_change_stock(): void
    {
        $this->get(route('stock.index'))->assertRedirectToRoute('login');
        $this->get(route('stock.show', 1))->assertRedirectToRoute('login');
        $this->post(route('stock.store', 1))->assertRedirectToRoute('login');
    }

    public function test_manual_entry_updates_stock_and_weighted_cost_and_appears_in_history(): void
    {
        $user = User::factory()->create(['name' => 'Joana Silva']);
        $product = Product::factory()->create(['name' => 'Café', 'stock_quantity' => 2, 'average_cost' => '10.0000']);

        $this->actingAs($user)->get(route('stock.index'))->assertOk()->assertSee('Café');

        $this->post(route('stock.store', $product), [
            'type' => StockMovement::ManualEntry,
            'quantity' => 3,
            'unit_cost' => '20.1234',
            'justification' => 'Saldo de abertura',
        ])->assertRedirectToRoute('stock.show', $product);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 5, 'average_cost' => '16.0740']);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'user_id' => $user->id, 'quantity_delta' => 3,
            'unit_cost' => '20.1234', 'type' => StockMovement::ManualEntry, 'justification' => 'Saldo de abertura',
        ]);
        $this->get(route('stock.show', $product))->assertOk()
            ->assertSee('Histórico de movimentações')->assertSee('Entrada manual')
            ->assertSee('Saldo de abertura')->assertSee('R$ 20,1234')->assertSee('Joana Silva');
    }

    public function test_manual_exit_keeps_the_average_cost_and_cannot_exceed_the_balance(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3, 'average_cost' => '12.4000']);
        $exit = ['type' => StockMovement::ManualExit, 'quantity' => 2];

        $this->actingAs(User::factory()->create())
            ->post(route('stock.store', $product), $exit)->assertRedirectToRoute('stock.show', $product);

        $this->from(route('stock.show', $product))
            ->post(route('stock.store', $product), $exit)->assertSessionHasErrors([
                'quantity' => 'A quantidade de saída excede o estoque disponível.',
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 1, 'average_cost' => '12.4000']);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity_delta' => -2]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_adjustment_requires_a_reason_and_uses_the_counted_balance(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $adjustment = ['type' => StockMovement::Adjustment, 'quantity' => 0];

        $this->actingAs(User::factory()->create())
            ->from(route('stock.show', $product))
            ->post(route('stock.store', $product), $adjustment)->assertSessionHasErrors([
                'justification' => 'Informe a justificativa do ajuste.',
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);

        $this->post(route('stock.store', $product), $adjustment + ['justification' => 'Contagem física'])
            ->assertRedirectToRoute('stock.show', $product);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'quantity_delta' => -3, 'justification' => 'Contagem física',
        ]);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_purchase_movements_are_linked_to_the_purchase_in_stock_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_on' => '2026-11-24',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => '10.00']],
        ])->assertRedirect();

        $purchase = Purchase::query()->sole();

        $this->get(route('stock.show', $product))->assertOk()
            ->assertSee('Entrada de compra')->assertSee(route('purchases.show', $purchase));
    }
}
