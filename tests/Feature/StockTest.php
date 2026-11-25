<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockAdjustmentReason;
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
        $this->get(route('stock.reasons.index'))->assertRedirectToRoute('login');
        $this->post(route('stock.reasons.store'))->assertRedirectToRoute('login');
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

    public function test_user_can_register_adjustment_reasons_but_cannot_repeat_a_name(): void
    {
        $details = ['name' => 'Contagem física'];

        $this->actingAs(User::factory()->create())->get(route('stock.reasons.index'))->assertOk()
            ->assertSee('Cadastrar motivo');

        $this->post(route('stock.reasons.store'), $details)->assertRedirectToRoute('stock.reasons.index');

        $this->assertDatabaseHas('motivos', $details);
        $this->get(route('stock.reasons.index'))->assertOk()->assertSee('Contagem física');

        $this->from(route('stock.reasons.index'))
            ->post(route('stock.reasons.store'), $details)
            ->assertSessionHasErrors(['name' => 'Este motivo já está cadastrado.']);

        $this->assertDatabaseCount('motivos', 1);
    }

    public function test_adjustment_requires_a_registered_reason_and_records_the_counted_balance(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $reason = StockAdjustmentReason::factory()->create(['name' => 'Contagem física']);
        $adjustment = ['type' => StockMovement::Adjustment, 'quantity' => 0];

        $this->actingAs(User::factory()->create())
            ->from(route('stock.show', $product))
            ->post(route('stock.store', $product), $adjustment)->assertSessionHasErrors([
                'reason_id' => 'Selecione um motivo cadastrado para o ajuste.',
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 3]);
        $this->assertDatabaseEmpty('ajustes_estoque');

        $this->post(route('stock.store', $product), $adjustment + ['reason_id' => $reason->id])
            ->assertRedirectToRoute('stock.show', $product);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
        $movement = $product->stockMovements()->sole();
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id, 'quantity_delta' => -3]);
        $this->assertDatabaseHas('ajustes_estoque', [
            'stock_movement_id' => $movement->id,
            'reason_id' => $reason->id,
            'previous_quantity' => 3,
            'counted_quantity' => 0,
        ]);
        $this->get(route('stock.show', $product))->assertOk()->assertSee('3 → 0')->assertSee('Contagem física');
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
