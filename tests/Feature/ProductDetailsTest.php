<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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
