<?php

namespace Tests\Feature;

use App\Models\Category;
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
}
