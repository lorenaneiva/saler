<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('categories.index'))->assertRedirectToRoute('login');
        $this->post(route('categories.store'))->assertRedirectToRoute('login');
        $this->patch(route('categories.update', 1))->assertRedirectToRoute('login');
        $this->delete(route('categories.destroy', 1))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_create_and_list_categories(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), ['name' => 'Bebidas'])
            ->assertRedirectToRoute('categories.index');

        $this->assertDatabaseHas('categories', ['name' => 'Bebidas']);
        $this->get(route('categories.index'))->assertOk()->assertSee('Bebidas');
    }

    public function test_authenticated_user_can_rename_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Bebidas']);

        $this->actingAs(User::factory()->create())
            ->get(route('categories.edit', $category))->assertOk()->assertSee('Bebidas');

        $this->patch(route('categories.update', $category), ['name' => 'Sucos'])
            ->assertRedirectToRoute('categories.index');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Sucos']);
    }

    public function test_name_is_required_on_creation_and_update(): void
    {
        $category = Category::factory()->create(['name' => 'Bebidas']);

        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), ['name' => ' '])
            ->assertSessionHasErrors(['name' => 'Informe o nome da categoria.']);

        $this->patch(route('categories.update', $category), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Informe o nome da categoria.']);

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Bebidas']);
    }

    public function test_authenticated_user_can_delete_an_unlinked_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('categories.destroy', $category))
            ->assertRedirectToRoute('categories.index');

        $this->assertModelMissing($category);
    }

    public function test_listing_shows_active_categories_by_default_and_filters_by_status(): void
    {
        Category::factory()->create(['name' => 'Bebidas']);
        Category::factory()->create(['name' => 'Laticínios', 'is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('categories.index'))->assertOk()
            ->assertSee('Bebidas')->assertDontSee('Laticínios');

        $this->get(route('categories.index', ['situacao' => 'inativas']))->assertOk()
            ->assertSee('Laticínios')->assertDontSee('Bebidas');

        $this->get(route('categories.index', ['situacao' => 'todas']))->assertOk()
            ->assertSee('Bebidas')->assertSee('Laticínios');
    }

    public function test_category_linked_to_a_product_offers_deactivation_instead_of_deletion(): void
    {
        $category = Category::factory()->create(['name' => 'Bebidas']);
        Product::factory()->for($category)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('categories.index'))->assertOk()
            ->assertSee(route('categories.deactivate', $category))
            ->assertSee('aria-label="Desativar Bebidas"', false)
            ->assertDontSee('aria-label="Excluir Bebidas"', false);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirectToRoute('categories.index')
            ->assertSessionHasErrors(['category' => 'Categorias que já tiveram produtos não podem ser excluídas; desative-as.']);

        $this->assertModelExists($category);
    }

    public function test_category_that_once_had_a_product_cannot_be_deleted_after_the_product_moves(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $product->update(['category_id' => Category::factory()->create()->id]);

        $this->assertTrue($category->fresh()->has_had_products);
        $this->actingAs(User::factory()->create())
            ->delete(route('categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($category);
    }

    public function test_category_can_be_deactivated_and_activated_again(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('categories.deactivate', $category))
            ->assertRedirectToRoute('categories.index')->assertSessionHas('status', 'Categoria desativada.');
        $this->assertFalse($category->fresh()->is_active);

        $inactiveListing = route('categories.index', ['situacao' => 'inativas']);
        $this->get($inactiveListing)->assertOk()
            ->assertSee(route('categories.activate', $category));

        $this->patch(route('categories.activate', $category))
            ->assertRedirect($inactiveListing)->assertSessionHas('status', 'Categoria ativada.');
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_inactive_category_cannot_be_chosen_for_new_products_but_is_kept_by_existing_ones(): void
    {
        $inactive = Category::factory()->create(['name' => 'Laticínios', 'is_active' => false]);
        $product = Product::factory()->for($inactive)->create(['name' => 'Queijo']);
        $supplier = Supplier::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('products.create'))->assertOk()->assertDontSee('Laticínios');
        $this->get(route('products.edit', $product))->assertOk()->assertSee('Laticínios (inativa)');

        $this->post(route('products.store'), ['name' => 'Iogurte', 'category_id' => $inactive->id, 'sale_price' => '5.00'])
            ->assertSessionHasErrors(['category_id' => 'Selecione uma categoria ativa.']);
        $this->patch(route('products.update', $product), ['name' => 'Queijo minas', 'category_id' => $inactive->id, 'sale_price' => '5.00'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Queijo minas', 'category_id' => $inactive->id]);

        $this->get(route('purchases.create'))->assertOk()->assertDontSee('Laticínios');
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_on' => '2026-11-24',
            'items' => [['new_product' => ['name' => 'Manteiga', 'category_id' => $inactive->id, 'sale_price' => '9.00'], 'quantity' => 1, 'unit_cost' => '4.00']],
        ])->assertSessionHasErrors(['items.0.new_product.category_id' => 'Selecione uma categoria ativa para o novo produto.']);
        $this->assertDatabaseMissing('products', ['name' => 'Manteiga']);
    }
}
