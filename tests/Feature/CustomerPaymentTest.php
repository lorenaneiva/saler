<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_register_payments(): void
    {
        $this->post(route('customers.payments.store', 1))->assertRedirectToRoute('login');
    }

    public function test_single_payment_settles_the_oldest_purchase_then_partly_pays_the_next(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $newerSale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-20', 'subtotal' => '200.00', 'total' => '200.00']);
        $olderSale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-10', 'subtotal' => '500.00', 'total' => '500.00']);
        Sale::factory()->create(['sold_on' => '2026-11-01', 'subtotal' => '300.00', 'total' => '300.00']);
        $form = $this->paymentDetails('600.00') + ['notes' => 'Pix recebido no balcão'];

        $this->actingAs($user)->get(route('customers.show', $customer))->assertOk()
            ->assertSee('R$ 700,00')->assertSee('Registrar pagamento');

        $this->post(route('customers.payments.store', $customer), $form)
            ->assertRedirectToRoute('customers.show', $customer);

        $payment = Payment::query()->sole();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id, 'customer_id' => $customer->id, 'user_id' => $user->id,
            'amount' => '600.00', 'method' => 'pix', 'notes' => 'Pix recebido no balcão',
        ]);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'sale_id' => $olderSale->id, 'amount' => '500.00']);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'sale_id' => $newerSale->id, 'amount' => '100.00']);
        $this->assertDatabaseCount('payment_allocations', 2);

        $this->get(route('customers.show', $customer))->assertOk()
            ->assertSeeTextInOrder(['Saldo em aberto', 'R$ 100,00'])
            ->assertSee('Quitada')->assertSee('Parcialmente paga')
            ->assertSee('Histórico de pagamentos')->assertSee('Pix recebido no balcão');
    }

    public function test_repeated_submission_is_recorded_once_and_only_reduces_the_oldest_debt(): void
    {
        $customer = Customer::factory()->create();
        $olderSale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-10', 'subtotal' => '500.00', 'total' => '500.00']);
        $newerSale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-10', 'subtotal' => '200.00', 'total' => '200.00']);
        $form = $this->paymentDetails('100.00');
        $url = route('customers.payments.store', $customer);

        $this->actingAs(User::factory()->create())->post($url, $form)->assertRedirectToRoute('customers.show', $customer);
        $this->post($url, $form)->assertRedirectToRoute('customers.show', $customer);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_allocations', 1);
        $this->assertDatabaseHas('payment_allocations', ['sale_id' => $olderSale->id, 'amount' => '100.00']);
        $this->assertDatabaseMissing('payment_allocations', ['sale_id' => $newerSale->id]);
        $this->get(route('customers.show', $customer))->assertOk()->assertSeeTextInOrder(['Saldo em aberto', 'R$ 600,00']);
    }

    public function test_payment_above_the_customers_own_balance_is_rejected_without_partial_writes(): void
    {
        $customer = Customer::factory()->create();
        Sale::factory()->for($customer)->create(['subtotal' => '50.00', 'total' => '50.00']);
        Sale::factory()->for($customer)->create(['subtotal' => '100.00', 'total' => '100.00', 'status' => 'cancelled']);
        Sale::factory()->create(['subtotal' => '200.00', 'total' => '200.00']);

        $this->actingAs(User::factory()->create())
            ->from(route('customers.show', $customer))
            ->followingRedirects()
            ->post(route('customers.payments.store', $customer), $this->paymentDetails('50.01'))
            ->assertOk()
            ->assertSee('O pagamento não pode ultrapassar o saldo em aberto do cliente.')
            ->assertSeeTextInOrder(['Saldo em aberto', 'R$ 50,00']);

        $this->assertDatabaseEmpty('payments');
        $this->assertDatabaseEmpty('payment_allocations');
    }

    /** @return array{submission_token: string, amount: string, paid_on: string, method: string} */
    private function paymentDetails(string $amount): array
    {
        return [
            'submission_token' => (string) Str::uuid(),
            'amount' => $amount,
            'paid_on' => '2026-11-24',
            'method' => 'pix',
        ];
    }
}
