<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ListSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_filter_runs_before_pagination_and_treats_wildcards_as_text(): void
    {
        Customer::factory()->count(21)->create(['name' => 'Cliente 10%']);
        Customer::factory()->create(['name' => 'Cliente 100']);

        $this->actingAs(User::factory()->create())
            ->get(route('customers.index', ['q' => '10%']))
            ->assertOk()->assertSeeText('1–20 de 21')->assertDontSeeText('Cliente 100')
            ->assertSee(route('customers.index', ['q' => '10%', 'page' => 2]));
    }

    public function test_searching_sale_items_and_payments_preserves_the_complete_sale_balance(): void
    {
        $sale = Sale::factory()->create(['total' => '20.00']);
        $product = Product::factory()->create(['barcode' => '000456', 'name' => 'Feijão']);
        $sale->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '20.00', 'unit_cost' => '5.00', 'subtotal' => '20.00']);
        foreach (['pix', 'cash'] as $method) {
            $payment = Payment::factory()->create(['customer_id' => $sale->customer_id, 'method' => $method, 'amount' => '5.00']);
            $payment->allocations()->create(['sale_id' => $sale->id, 'amount' => '5.00']);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('sales.show', [$sale, 'q_itens' => '000456', 'q_pagamentos' => 'dinheiro']))
            ->assertOk()->assertSeeText('Feijão')->assertSeeText('Dinheiro')->assertDontSeeText('Pix')
            ->assertSeeTextInOrder(['Valor pago', 'R$ 10,00', 'Saldo em aberto', 'R$ 10,00']);

        $other = Sale::factory()->create();
        $this->get(route('sales.index', ['q' => 'feijão']))->assertOk()
            ->assertSee(route('sales.show', $sale))->assertDontSee(route('sales.show', $other));
    }

    public function test_clear_filters_button_is_always_rendered_and_only_enabled_with_active_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $disabledButton = '<span class="button-quiet clear-filters" aria-disabled="true"';
        $enabledButton = '<a href="'.route('products.index').'" class="button-quiet clear-filters">Limpar</a>';

        $this->get(route('products.index'))->assertOk()->assertSee($disabledButton, false)->assertDontSee($enabledButton, false);
        $this->get(route('products.index', ['situacao' => 'ativos']))->assertOk()->assertSee($enabledButton, false)->assertDontSee($disabledButton, false);

        $this->get(route('customers.index'))->assertOk()->assertSee($disabledButton, false);
        $this->get(route('customers.index', ['q' => 'Ana']))->assertOk()->assertDontSee($disabledButton, false)
            ->assertSee('<a href="'.route('customers.index').'" class="button-quiet clear-filters">Limpar</a>', false);

        $this->get(route('categories.index'))->assertOk()->assertSee($disabledButton, false);
        $this->get(route('categories.index', ['situacao' => 'todas']))->assertOk()->assertDontSee($disabledButton, false)
            ->assertSee('<a href="'.route('categories.index').'" class="button-quiet clear-filters">Limpar</a>', false);
    }
}
