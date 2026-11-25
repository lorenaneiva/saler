<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_list_or_change_suppliers(): void
    {
        $this->get(route('suppliers.index'))->assertRedirectToRoute('login');
        $this->post(route('suppliers.store'))->assertRedirectToRoute('login');
        $this->patch(route('suppliers.update', 1))->assertRedirectToRoute('login');
        $this->delete(route('suppliers.destroy', 1))->assertRedirectToRoute('login');
    }

    public function test_user_can_create_and_list_an_active_supplier(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('suppliers.create'))->assertOk()->assertSee('Novo fornecedor');

        $this->post(route('suppliers.store'), [
            'name' => 'Distribuidora Central',
            'contact' => 'vendas@example.test',
            'is_active' => false,
        ])->assertRedirectToRoute('suppliers.index');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Distribuidora Central',
            'contact' => 'vendas@example.test',
            'representative' => null,
            'is_active' => true,
        ]);

        $this->get(route('suppliers.index'))->assertOk()
            ->assertSee('Distribuidora Central')->assertSee('vendas@example.test')->assertSee('Ativo');
    }

    public function test_user_can_update_supplier_contact_and_representative(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Distribuidora Central']);

        $this->actingAs(User::factory()->create())
            ->get(route('suppliers.edit', $supplier))->assertOk()->assertSee('Distribuidora Central');

        $this->patch(route('suppliers.update', $supplier), [
            'name' => 'Distribuidora Central',
            'contact' => '(91) 99999-0000',
            'representative' => 'Ana Costa',
        ])->assertRedirectToRoute('suppliers.index');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'contact' => '(91) 99999-0000',
            'representative' => 'Ana Costa',
        ]);
    }

    public function test_name_and_contact_are_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('suppliers.create'))
            ->post(route('suppliers.store'), ['name' => ' ', 'representative' => ['Ana']])
            ->assertRedirectToRoute('suppliers.create')
            ->assertSessionHasErrors([
                'name' => 'Informe o campo nome.',
                'contact' => 'Informe o campo contato.',
                'representative' => 'O campo representante deve ser um texto.',
            ]);

        $this->get(route('suppliers.create'))->assertOk();
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_deactivating_a_supplier_preserves_the_record(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('suppliers.destroy', $supplier))->assertRedirectToRoute('suppliers.index');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'is_active' => false]);
        $this->get(route('suppliers.index'))->assertOk()->assertSee('Inativo')->assertDontSee('Desativar');
    }
}
