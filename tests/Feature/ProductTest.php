<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_the_product_catalog_or_deactivate_products(): void
    {
        $this->get(route('products.index'))->assertRedirectToRoute('login');
        $this->delete(route('products.destroy', 1))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_create_and_list_products(): void
    {
        $category = Category::factory()->create(['name' => 'Mercearia']);

        $this->actingAs(User::factory()->create())
            ->get(route('products.create'))->assertOk()->assertSee('Mercearia');

        $this->post(route('products.store'), [
            'name' => 'Arroz',
            'category_id' => $category->id,
            'sale_price' => '13.99',
            'barcode' => '012345',
            'supplier_name' => 'Distribuidora Local',
            'stock_quantity' => 99,
            'average_cost' => 10,
            'is_active' => false,
        ])->assertRedirectToRoute('products.index');

        $this->assertDatabaseHas('products', [
            'name' => 'Arroz',
            'category_id' => $category->id,
            'sale_price' => '13.99',
            'barcode' => '012345',
            'supplier_name' => 'Distribuidora Local',
            'stock_quantity' => 0,
            'average_cost' => 0,
            'is_active' => true,
        ]);

        $this->get(route('products.index'))->assertOk()->assertSee('Arroz')->assertSee('R$ 13,99');
    }

    public function test_product_requires_an_existing_category_and_valid_price(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => 999,
                'sale_price' => '19.999',
            ])->assertRedirectToRoute('products.create')->assertSessionHasErrors([
                'name' => 'Informe o nome do produto.',
                'category_id' => 'A categoria selecionada não existe.',
                'sale_price' => 'Informe o preço com até duas casas decimais.',
            ]);

        $this->get(route('products.create'))->assertOk()->assertSee('Cadastre uma categoria');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_authenticated_user_can_update_a_product_and_view_its_margin(): void
    {
        $product = Product::factory()->create(['name' => 'Arroz', 'average_cost' => '7.5000']);

        $this->actingAs(User::factory()->create())
            ->get(route('products.edit', $product))->assertOk()->assertSee('Arroz');

        $this->patch(route('products.update', $product), [
            'name' => 'Arroz integral',
            'category_id' => $product->category_id,
            'sale_price' => '12.00',
        ])->assertRedirectToRoute('products.index');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Arroz integral', 'sale_price' => '12.00']);
        $this->get(route('products.index'))->assertOk()->assertSee('37,50%');
    }

    public function test_deactivation_preserves_the_product_and_its_category_link(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('products.destroy', $product))->assertRedirectToRoute('products.index');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        $this->get(route('products.index'))->assertOk()->assertSee('Inativo');

        $this->delete(route('categories.destroy', $product->category_id))
            ->assertRedirectToRoute('categories.index')
            ->assertSessionHasErrors(['category' => 'Categorias vinculadas a produtos não podem ser excluídas.']);

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }
}
