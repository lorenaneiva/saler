<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAdjustmentReason;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TableSortTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['name' => 'Responsável']));
    }

    public function test_prices_sort_numerically_across_every_page_not_only_the_current_one(): void
    {
        Product::factory()->count(20)->create(['sale_price' => '50.00']);
        Product::factory()->create(['name' => 'Mais barato', 'sale_price' => '9.00']);
        Product::factory()->create(['name' => 'Mais caro', 'sale_price' => '100.00']);

        $this->get(route('products.index', ['ordem' => 'preco']))->assertOk()
            ->assertSeeText('Mais barato')->assertDontSeeText('Mais caro');
        $this->get(route('products.index', ['ordem' => '-preco']))->assertOk()
            ->assertSeeText('Mais caro')->assertDontSeeText('Mais barato');
    }

    public function test_names_sort_alphabetically_ignoring_case_and_accents(): void
    {
        foreach (['banana', 'Zebra', 'Água'] as $name) {
            Customer::factory()->create(['name' => $name]);
        }

        $this->get(route('customers.index'))->assertOk()->assertSeeTextInOrder(['Água', 'banana', 'Zebra']);
        $this->get(route('customers.index', ['ordem' => '-nome']))->assertOk()->assertSeeTextInOrder(['Zebra', 'banana', 'Água']);
    }

    public function test_dates_and_quantities_sort_chronologically_and_numerically(): void
    {
        foreach (['2026-01-10' => 'Primeira', '2026-03-05' => 'Terceira', '2026-02-01' => 'Segunda'] as $date => $name) {
            Sale::factory()->for(Customer::factory()->create(['name' => $name]))->create(['sold_on' => $date]);
        }

        $this->get(route('sales.index'))->assertOk()->assertSeeTextInOrder(['Terceira', 'Segunda', 'Primeira']);
        $this->get(route('sales.index', ['ordem' => 'data']))->assertOk()->assertSeeTextInOrder(['Primeira', 'Segunda', 'Terceira']);

        foreach ([2 => 'Dois', 10 => 'Dez', 9 => 'Nove'] as $quantity => $name) {
            Product::factory()->create(['name' => $name, 'stock_quantity' => $quantity]);
        }

        $this->get(route('products.index', ['ordem' => 'estoque']))->assertOk()->assertSeeTextInOrder(['Dois', 'Nove', 'Dez']);
    }

    public function test_unknown_values_stay_at_the_end_in_both_directions(): void
    {
        $withCost = Product::factory()->create(['name' => 'Com custo', 'average_cost' => '4.0000']);
        $withCost->stockMovements()->create(['user_id' => auth()->id(), 'quantity_delta' => 1, 'unit_cost' => '4.0000', 'type' => StockMovement::PurchaseEntry]);
        Product::factory()->create(['name' => 'Sem custo', 'average_cost' => '0.0000']);

        foreach (['custo', '-custo'] as $ordering) {
            $this->get(route('products.index', ['ordem' => $ordering]))->assertOk()->assertSeeTextInOrder(['Com custo', 'Sem custo']);
        }
    }

    public function test_default_ordering_is_highlighted_and_header_links_toggle_direction(): void
    {
        $this->get(route('sales.index'))->assertOk()
            ->assertSee('<th scope="col" aria-sort="descending" class="sortable is-sorted">', false)
            ->assertSee('href="'.e(route('sales.index', ['ordem' => 'data'])).'" class="sort-link"', false)
            ->assertSee('href="'.e(route('sales.index', ['ordem' => 'total'])).'" class="sort-link"', false);

        $this->get(route('sales.index', ['ordem' => 'total']))->assertOk()
            ->assertSee('<th scope="col" aria-sort="ascending" class="sortable is-sorted money">', false)
            ->assertSee('href="'.e(route('sales.index', ['ordem' => '-total'])).'" class="sort-link"', false);

        $this->get(route('sales.index', ['ordem' => 'data']))->assertOk()
            ->assertSee('href="'.e(route('sales.index')).'" class="sort-link"', false);
    }

    public function test_changing_the_ordering_keeps_filters_and_returns_to_the_first_page(): void
    {
        Category::factory()->count(20)->create(['name' => 'Bebidas', 'is_active' => false]);

        $response = $this->get(route('categories.index', ['q' => 'Bebidas', 'situacao' => 'todas', 'page' => 2]))->assertOk();

        $response->assertSee('href="'.e(route('categories.index', ['q' => 'Bebidas', 'situacao' => 'todas', 'ordem' => 'produtos'])).'" class="sort-link"', false);
    }

    public function test_searching_and_paginating_keep_the_chosen_ordering(): void
    {
        Customer::factory()->count(20)->create(['name' => 'Cliente Silva']);

        $response = $this->get(route('customers.index', ['q' => 'Silva', 'ordem' => '-email']))->assertOk()
            ->assertSee('href="'.e(route('customers.index', ['q' => 'Silva', 'ordem' => '-email', 'page' => 2])).'" rel="next"', false);

        $searchForm = str($response->getContent())->betweenFirst('id="q-search"', '</form>');
        $this->assertStringContainsString('<input type="hidden" name="ordem" value="-email">', $searchForm);
    }

    public function test_unknown_orderings_fall_back_to_the_default(): void
    {
        Customer::factory()->create(['name' => 'Bruno']);
        Customer::factory()->create(['name' => 'Ana']);

        $this->get(route('customers.index', ['ordem' => 'senha']))->assertOk()->assertSeeTextInOrder(['Ana', 'Bruno']);
    }

    public function test_tables_sharing_a_page_sort_independently(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '30.00', 'total' => '30.00']);
        foreach (['Arroz' => '10.00', 'Feijão' => '20.00'] as $name => $price) {
            $product = Product::factory()->create(['name' => $name]);
            $sale->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $price, 'unit_cost' => '1.00', 'subtotal' => $price]);
        }
        foreach (['pix' => '2026-01-01', 'cash' => '2026-02-01'] as $method => $date) {
            $payment = Payment::factory()->create(['customer_id' => $sale->customer_id, 'method' => $method, 'paid_on' => $date]);
            $payment->allocations()->create(['sale_id' => $sale->id, 'amount' => '5.00']);
        }

        $this->get(route('sales.show', [$sale, 'ordem_itens' => '-preco', 'ordem_pagamentos' => '-forma']))->assertOk()
            ->assertSeeTextInOrder(['Feijão', 'Arroz', 'Pix', 'Dinheiro']);
    }

    public function test_financial_status_sorts_from_pending_to_settled(): void
    {
        foreach (['Quitada' => '15.00', 'Parcial' => '5.00', 'Pendente' => null] as $name => $paid) {
            $sale = Sale::factory()->for(Customer::factory()->create(['name' => $name]))->create();
            if ($paid !== null) {
                Payment::factory()->create(['customer_id' => $sale->customer_id, 'amount' => $paid])
                    ->allocations()->create(['sale_id' => $sale->id, 'amount' => $paid]);
            }
        }

        $this->get(route('sales.index', ['ordem' => 'situacao']))->assertOk()->assertSeeTextInOrder(['Pendente', 'Parcial', 'Quitada']);
    }

    public function test_every_sortable_column_orders_in_both_directions(): void
    {
        $product = Product::factory()->create();
        $sale = Sale::factory()->create();
        $sale->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '15.00', 'unit_cost' => '1.00', 'subtotal' => '15.00']);
        Payment::factory()->create(['customer_id' => $sale->customer_id])->allocations()->create(['sale_id' => $sale->id, 'amount' => '10.00']);
        $purchase = Purchase::factory()->create();
        $purchase->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => '1.00', 'discount' => '0.00', 'total' => '1.00']);
        $movement = $product->stockMovements()->create(['user_id' => auth()->id(), 'quantity_delta' => -1, 'unit_cost' => '1.0000', 'type' => StockMovement::ManualExit, 'justification' => 'Quebra']);
        $movement->adjustment()->create(['reason_id' => StockAdjustmentReason::factory()->create()->id, 'previous_quantity' => 2, 'counted_quantity' => 1]);

        $tables = [
            [route('categories.index'), 'ordem', ['nome', 'produtos', 'situacao']],
            [route('products.index'), 'ordem', ['nome', 'categoria', 'preco', 'custo', 'margem', 'estoque', 'situacao']],
            [route('customers.index'), 'ordem', ['nome', 'telefone', 'email']],
            [route('suppliers.index'), 'ordem', ['nome', 'contato', 'representante', 'situacao']],
            [route('purchases.index'), 'ordem', ['numero', 'fornecedor', 'data', 'total']],
            [route('purchases.show', $purchase), 'ordem_itens', ['produto', 'quantidade', 'custo', 'desconto', 'total']],
            [route('sales.index'), 'ordem', ['numero', 'cliente', 'data', 'total', 'situacao']],
            [route('sales.show', $sale), 'ordem_itens', ['produto', 'quantidade', 'preco', 'subtotal']],
            [route('sales.show', $sale), 'ordem_pagamentos', ['numero', 'data', 'forma', 'valor']],
            [route('customers.show', $sale->customer_id), 'ordem_compras', ['numero', 'data', 'total', 'pago', 'aberto', 'situacao']],
            [route('customers.show', [$sale->customer_id, 'secao' => 'pagamentos']), 'ordem_pagamentos', ['numero', 'data', 'forma', 'valor', 'observacao']],
            [route('products.show', $product), 'ordem', ['data', 'tipo', 'origem', 'quantidade', 'saldo', 'custo', 'preco', 'prejuizo', 'responsavel', 'motivo']],
            [route('products.reasons.index'), 'ordem', ['motivo']],
        ];

        foreach ($tables as [$url, $parameter, $columns]) {
            foreach ($columns as $column) {
                foreach ([$column, '-'.$column] as $ordering) {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $this->get($url.$separator.http_build_query([$parameter => $ordering]))->assertOk()
                        ->assertSee('aria-sort="'.($ordering[0] === '-' ? 'descending' : 'ascending').'"', false);
                }
            }
        }
    }

    public function test_action_columns_are_not_sortable(): void
    {
        $this->get(route('categories.index'))->assertOk()->assertSee('<th scope="col">Ações</th>', false);
    }
}
