<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SaleDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_items_footer_shows_complete_sale_totals_while_search_filters_the_products(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '30.00', 'discount' => '5.00', 'total' => '25.00']);
        foreach (['Arroz' => '10.00', 'Feijão' => '20.00'] as $name => $subtotal) {
            $product = Product::factory()->create(['name' => $name]);
            $sale->items()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => $subtotal, 'unit_cost' => '1.00', 'subtotal' => $subtotal]);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('sales.show', [$sale, 'q_itens' => 'feijão']))
            ->assertOk()
            ->assertSeeText('Produtos da venda')->assertSeeText('1 de 2 itens')
            ->assertSeeText('Feijão')->assertDontSeeText('Arroz')
            ->assertSeeTextInOrder(['Subtotal', 'R$ 30,00', 'Desconto', '− R$ 5,00', 'Total da venda', 'R$ 25,00'])
            ->assertDontSee('>Buscar</button>', false);
    }

    public function test_summary_keeps_only_customer_date_and_total_and_highlights_outstanding_balance(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '30.00', 'discount' => '5.00', 'total' => '25.00']);

        $response = $this->actingAs(User::factory()->create())->get(route('sales.show', $sale))->assertOk();

        $summary = str($response->getContent())->betweenFirst('aria-label="Resumo da venda"', '</section>');
        $this->assertStringContainsString('href="'.route('customers.show', $sale->customer_id).'">'.e($sale->customer->name).'</a>', $summary);
        $this->assertStringContainsString('Total da venda', $summary);
        $this->assertStringNotContainsString('Subtotal', $summary);
        $this->assertStringNotContainsString('Desconto', $summary);
        $this->assertStringContainsString('class="sale-outstanding"', $summary);
        $this->assertStringContainsString('Pendente', $summary);
    }

    public function test_payment_search_is_disabled_until_the_sale_has_linked_payments(): void
    {
        $sale = Sale::factory()->create(['total' => '20.00']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('sales.show', $sale))->assertOk()
            ->assertSeeText('0 pagamentos')
            ->assertSeeText('Nenhum pagamento vinculado a esta venda.')
            ->assertSee('name="q_pagamentos" value="" maxlength="100" placeholder="Número, forma ou observação" autocomplete="off" class="field" disabled', false);

        $payment = Payment::factory()->create(['customer_id' => $sale->customer_id, 'method' => 'pix', 'amount' => '5.00']);
        $payment->allocations()->create(['sale_id' => $sale->id, 'amount' => '5.00']);

        $this->get(route('sales.show', [$sale, 'q_pagamentos' => 'dinheiro']))->assertOk()
            ->assertSeeText('0 de 1 pagamento')
            ->assertSeeText('Nenhum pagamento corresponde à busca.')
            ->assertDontSee('class="field" disabled', false);
    }

    public function test_sales_list_links_the_customer_name_to_the_customer_page(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('sales.index'))->assertOk()
            ->assertSee('<a class="cell-link" href="'.route('customers.show', $sale->customer_id).'">'.e($sale->customer->name).'</a>', false)
            ->assertSee(route('sales.show', $sale));
    }
}
