<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductDrawerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_edit_drawer_loads_current_product_values_and_readonly_availability(): void
    {
        $product = Product::factory()->create([
            'name' => 'Arroz <Integral>', 'sale_price' => '12.00', 'average_cost' => '7.5000', 'stock_quantity' => 8,
        ]);

        $response = $this->actingAs(User::factory()->create())->getJson(route('products.edit', $product))->assertOk();

        $html = $response->json('html');
        $this->assertStringContainsString(route('products.update', $product), $html);
        $this->assertStringContainsString('Arroz &lt;Integral&gt;', $html);
        $this->assertStringContainsString('37,50%', $html);
        $this->assertStringContainsString('8 un.', $html);
        $this->assertStringContainsString('R$ 7,50', $html);
        $this->assertStringContainsString('data-average-cost="7.5000"', $html);
        $this->assertStringNotContainsString('name="stock_quantity"', $html);
        $this->assertStringNotContainsString('name="average_cost"', $html);
        $this->assertStringNotContainsString('name="is_active"', $html);
    }

    public function test_new_product_drawer_uses_the_same_form_and_explains_missing_categories(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson(route('products.create'))->assertOk();

        $this->assertStringContainsString('Cadastre uma categoria', $response->json('html'));
        $this->assertStringNotContainsString('data-product-save', $response->json('html'));

        Category::factory()->create();

        $response = $this->getJson(route('products.create'))->assertOk();

        $this->assertStringContainsString(route('products.store'), $response->json('html'));
        $this->assertStringContainsString('data-product-form', $response->json('html'));
        $this->assertStringContainsString('Cadastrar produto', $response->json('html'));
    }

    public function test_drawer_creation_returns_confirmation_and_preserves_stock_business_rules(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create())->postJson(route('products.store'), [
            'name' => 'Feijão', 'category_id' => $category->id, 'sale_price' => '1234.56', 'barcode' => '000123',
            'stock_quantity' => 999, 'average_cost' => '100.00', 'is_active' => false,
        ])->assertCreated()->assertExactJson(['message' => 'Produto cadastrado.']);

        $this->assertDatabaseHas('products', [
            'name' => 'Feijão', 'sale_price' => '1234.56', 'barcode' => '000123',
            'stock_quantity' => 0, 'average_cost' => '0.0000', 'is_active' => true,
        ]);
    }

    public function test_drawer_creation_returns_field_errors_without_creating_a_product(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('products.store'), [
            'name' => '', 'sale_price' => '19.999',
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name' => 'Informe o nome do produto.',
            'category_id' => 'Selecione uma categoria.',
            'sale_price' => 'Informe o preço com até duas casas decimais.',
        ]);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_refreshing_the_filtered_page_after_saving_keeps_pagination_and_updated_values(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->for($category)->create(['name' => 'Arroz A']);
        $product = Product::factory()->for($category)->create(['name' => 'Arroz Z', 'sale_price' => '10.00']);
        $this->actingAs(User::factory()->create());

        $this->patchJson(route('products.update', $product), [
            'name' => 'Arroz Z', 'category_id' => $category->id, 'sale_price' => '15.50',
        ])->assertOk();

        $this->get(route('products.index', ['q' => 'Arroz', 'categoria' => $category->id, 'situacao' => 'ativos', 'page' => 2]))
            ->assertOk()->assertSee('Arroz Z')->assertSee('R$ 15,50')->assertDontSee('Arroz A')
            ->assertSee('data-product-list', false)
            ->assertViewHas('products', fn ($products): bool => $products->currentPage() === 2 && $products->total() === 21);
    }

    public function test_guests_cannot_load_or_submit_product_drawer_forms(): void
    {
        $product = Product::factory()->create();

        $this->getJson(route('products.create'))->assertUnauthorized();
        $this->getJson(route('products.edit', $product))->assertUnauthorized();
        $this->postJson(route('products.store'), ['name' => 'Produto'])->assertUnauthorized();

        $this->assertDatabaseCount('products', 1);
    }
}
