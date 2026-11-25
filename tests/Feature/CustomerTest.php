<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login_when_accessing_customers(): void
    {
        $this->get(route('customers.index'))->assertRedirectToRoute('login');
        $this->get(route('customers.show', 1))->assertRedirectToRoute('login');
        $this->post(route('customers.store'))->assertRedirectToRoute('login');
        $this->patch(route('customers.update', 1))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_create_list_and_view_a_customer(): void
    {
        $user = User::factory()->create();
        $details = [
            'name' => 'Maria Silva',
            'phone' => '(91) 99999-0000',
            'cpf' => '012.345.678-90',
            'email' => 'maria@example.test',
            'postal_code' => '66000-000',
        ];

        $this->actingAs($user)->get(route('customers.create'))->assertOk()->assertSee('Novo cliente');
        $response = $this->post(route('customers.store'), $details);
        $customer = Customer::query()->sole();

        $response->assertRedirectToRoute('customers.show', $customer);
        $this->assertDatabaseHas('customers', $details);

        $this->get(route('customers.index'))->assertOk()->assertSee('Maria Silva')->assertDontSee($details['cpf']);
        $this->get(route('customers.show', $customer))->assertOk()->assertSee($details['cpf'])->assertSee($details['postal_code']);
    }

    public function test_authenticated_user_can_update_a_customer_and_clear_optional_fields(): void
    {
        $customer = Customer::factory()->create(['email' => 'anterior@example.test']);

        $this->actingAs(User::factory()->create())
            ->get(route('customers.edit', $customer))->assertOk()->assertSee('anterior@example.test');

        $this->patch(route('customers.update', $customer), [
            'name' => 'Maria <Silva>',
            'phone' => '91999990000',
            'email' => '',
        ])->assertRedirectToRoute('customers.show', $customer);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Maria <Silva>', 'email' => null]);
        $this->get(route('customers.show', $customer))->assertOk()->assertSee('Maria &lt;Silva&gt;', false);
    }

    public function test_profile_shows_only_the_customers_purchases_and_open_balance(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $firstSale = Sale::factory()->for($customer)->create([
            'user_id' => $user->id,
            'sold_on' => '2026-11-20', 'subtotal' => '39.50', 'discount' => '0.00', 'total' => '39.50',
        ]);
        $secondSale = Sale::factory()->for($customer)->create([
            'user_id' => $user->id,
            'sold_on' => '2026-11-24', 'subtotal' => '24.80', 'discount' => '0.00', 'total' => '24.80',
        ]);
        $cancelledSale = Sale::factory()->for($customer)->create([
            'user_id' => $user->id,
            'sold_on' => '2026-11-22', 'status' => 'cancelled',
        ]);
        $otherSale = Sale::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('customers.show', $customer))->assertOk()
            ->assertSee('Saldo em aberto')
            ->assertSee('R$ 64,30')
            ->assertSee('Histórico de compras')
            ->assertSeeInOrder([
                route('sales.show', $secondSale),
                route('sales.show', $cancelledSale),
                route('sales.show', $firstSale),
            ])
            ->assertDontSee(route('sales.show', $otherSale));
    }

    public function test_profile_without_purchases_shows_zero_balance(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('customers.show', $customer))->assertOk()
            ->assertSee('R$ 0,00')->assertSee('Nenhuma compra registrada para este cliente.');
    }

    public function test_customer_with_missing_required_fields_or_invalid_contact_details_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('customers.create'))
            ->post(route('customers.store'), [
                'name' => ' ',
                'email' => 'email-invalido',
                'cpf' => '123',
                'postal_code' => '123',
            ])
            ->assertRedirectToRoute('customers.create')
            ->assertSessionHasErrors([
                'name' => 'Informe o campo nome.',
                'phone' => 'Informe o campo telefone.',
                'email' => 'Informe um e-mail válido.',
                'cpf' => 'Informe o CPF com 11 dígitos, com ou sem pontuação.',
                'postal_code' => 'Informe o CEP com 8 dígitos, com ou sem hífen.',
            ]);

        $this->assertDatabaseCount('customers', 0);
    }
}
