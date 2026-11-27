<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['name' => 'Joana Silva']));
    }

    public function test_details_page_gathers_registration_pricing_stock_and_movements(): void
    {
        $product = Product::factory()
            ->for(Category::factory()->create(['name' => 'Mercearia']))
            ->for(Supplier::factory()->create(['name' => 'Distribuidora Local']))
            ->withRegisteredCost()
            ->create(['name' => 'Café', 'barcode' => '7891234567890', 'sale_price' => '20.00', 'average_cost' => '12.5000', 'stock_quantity' => 4]);
        $product->stockMovements()->create(['user_id' => auth()->id(), 'quantity_delta' => -1, 'unit_cost' => '12.5000', 'type' => StockMovement::ManualExit, 'justification' => 'Pacote rasgado']);

        $this->get(route('products.show', $product))->assertOk()
            ->assertSeeText('Café')
            ->assertSeeTextInOrder(['Categoria', 'Mercearia', 'Fornecedor', 'Distribuidora Local', 'Código de barras', '7891234567890', 'Situação', 'Ativo'])
            ->assertSeeTextInOrder(['Preço de venda', 'R$ 20,00', 'Custo médio', 'R$ 12,50', 'Margem de lucro', '37,50%', 'Saldo em estoque', '4 un.', 'Valor em estoque (custo)', 'R$ 50,00'])
            ->assertSee('href="'.route('products.edit', $product).'" data-product-open', false)
            ->assertSee('data-dialog-open="stock-movement"', false)
            ->assertSee('action="'.route('products.movements.store', $product).'"', false)
            ->assertSeeTextInOrder(['Histórico de movimentações', 'Perda de estoque', 'Joana Silva', 'Pacote rasgado'])
            ->assertSee('id="product-editor"', false);
    }

    public function test_history_shows_balance_after_each_movement_and_the_historical_sale_price(): void
    {
        $product = Product::factory()->create(['name' => 'Café', 'sale_price' => '18.00', 'stock_quantity' => 10]);
        $purchase = Purchase::factory()->create();
        $purchaseItem = $purchase->items()->create(['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => '7.0000', 'discount' => '0.00', 'total' => '70.00']);
        $sale = Sale::factory()->create();
        $saleItem = $sale->items()->create(['product_id' => $product->id, 'quantity' => 3, 'unit_price' => '12.50', 'unit_cost' => '7.0000', 'subtotal' => '37.50']);

        $movements = [
            ['2026-01-01 09:00', 10, StockMovement::PurchaseEntry, ['purchase_item_id' => $purchaseItem->id]],
            ['2026-01-02 09:00', -3, StockMovement::SaleExit, ['sale_item_id' => $saleItem->id]],
            ['2026-01-03 09:00', -2, StockMovement::ManualExit, ['justification' => 'Avaria']],
            ['2026-01-04 09:00', 5, StockMovement::ManualEntry, ['justification' => 'Doação']],
        ];
        foreach ($movements as [$moment, $delta, $type, $links]) {
            $this->travelTo($moment);
            $product->stockMovements()->create(['user_id' => auth()->id(), 'quantity_delta' => $delta, 'unit_cost' => '7.0000', 'type' => $type, ...$links]);
        }
        $this->travelBack();

        $balances = fn (array $query): array => $this->get(route('products.show', [$product, ...$query]))->assertOk()
            ->viewData('movements')->mapWithKeys(fn (StockMovement $movement): array => [$movement->quantity_delta => $movement->balance_after])->all();

        $this->assertSame([5 => 10, -2 => 5, -3 => 7, 10 => 10], $balances([]));
        $this->assertSame([-3 => 7, -2 => 5, 5 => 10, 10 => 10], $balances(['ordem' => 'quantidade']));
        $this->assertSame([-2 => 5], $balances(['q' => 'Avaria']));
        $this->assertSame($product->stock_quantity, $product->stockMovements()->sum('quantity_delta'));

        $response = $this->get(route('products.show', [$product, 'ordem' => 'data']))->assertOk();
        $history = str($response->getContent())->betweenFirst('id="stock-movements-results"', '</table>');
        $this->assertStringNotContainsString('R$ 18,00', $history);
        $response
            ->assertSeeInOrder(['Preço unitário de venda', 'Entrada de compra', 'R$ 7,00', '—', 'Venda', 'R$ 7,00', 'R$ 12,50', 'Perda de estoque', 'R$ 7,00', '—'], false)
            ->assertSee('<a class="text-link whitespace-nowrap" href="'.route('purchases.show', $purchase).'"', false)
            ->assertSee('<a class="text-link whitespace-nowrap" href="'.route('sales.show', $sale).'"', false);
    }

    public function test_stock_value_is_unknown_until_a_purchase_records_the_cost(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 3, 'average_cost' => '0.0000']);

        $this->get(route('products.show', $product))->assertOk()
            ->assertSeeTextInOrder(['Valor em estoque (custo)', 'Sem custo registrado']);
    }

    public function test_listing_filters_by_stock_availability_and_links_each_name_to_its_details(): void
    {
        $inStock = Product::factory()->create(['name' => 'Arroz', 'stock_quantity' => 5]);
        Product::factory()->create(['name' => 'Feijão', 'stock_quantity' => 0]);

        $this->get(route('products.index', ['estoque' => 'com']))->assertOk()->assertSeeText('Arroz')->assertDontSeeText('Feijão')
            ->assertSee('<option value="com" selected>Com estoque</option>', false);
        $this->get(route('products.index', ['estoque' => 'sem']))->assertOk()->assertSeeText('Feijão')->assertDontSeeText('Arroz');
        $this->get(route('products.index', ['estoque' => 'baixo']))->assertOk()->assertSeeText('Arroz')->assertSeeText('Feijão');

        $this->get(route('products.index'))->assertOk()
            ->assertSee('<a data-row-link class="row-title" href="'.route('products.show', $inStock).'">Arroz</a>', false)
            ->assertSee('href="'.route('products.reasons.index').'"', false);
    }

    public function test_stock_module_is_gone_from_navigation_and_old_addresses_redirect(): void
    {
        $product = Product::factory()->create();

        $this->get(route('dashboard'))->assertOk()->assertDontSee('/estoque', false)->assertDontSeeText('Estoque');

        $this->get('/estoque')->assertStatus(301)->assertRedirect(route('products.index'));
        $this->get('/estoque/motivos')->assertStatus(301)->assertRedirect(route('products.reasons.index'));
        $this->get('/estoque/'.$product->id)->assertStatus(301)->assertRedirect(route('products.show', $product));
    }

    public function test_reasons_page_belongs_to_the_products_module(): void
    {
        $this->get(route('products.reasons.index'))->assertOk()
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSeeInOrder(['href="'.route('products.index').'" class="module-link"', 'aria-current="page"', 'Produtos'], false)
            ->assertSee('action="'.route('products.reasons.store').'"', false);
    }
}
