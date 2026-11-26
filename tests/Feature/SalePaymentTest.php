<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalePaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_register_sale_payments(): void
    {
        $this->post(route('sales.payments.store', Sale::factory()->create()))->assertRedirectToRoute('login');
        $this->assertDatabaseEmpty('payments');
    }

    public function test_partial_payment_is_allocated_only_to_the_viewed_sale_instead_of_the_oldest_one(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create();
        $olderSale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-01', 'subtotal' => '300.00', 'total' => '300.00']);
        $sale = Sale::factory()->for($customer)->create(['sold_on' => '2026-11-20', 'subtotal' => '200.00', 'total' => '200.00']);

        $this->actingAs($user)->from(route('sales.show', [$sale, 'q_itens' => 'arroz']))
            ->post(route('sales.payments.store', $sale), $this->paymentDetails('80.00') + ['notes' => 'Sinal'])
            ->assertRedirect(route('sales.show', [$sale, 'q_itens' => 'arroz']))
            ->assertSessionHasNoErrors();

        $payment = Payment::query()->sole();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'customer_id' => $customer->id, 'user_id' => $user->id, 'amount' => '80.00', 'notes' => 'Sinal']);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $payment->id, 'sale_id' => $sale->id, 'amount' => '80.00']);
        $this->assertDatabaseMissing('payment_allocations', ['sale_id' => $olderSale->id]);

        $this->get(route('sales.show', $sale))->assertOk()
            ->assertSeeText('Pagamento registrado nesta venda e saldo atualizado.')
            ->assertSeeTextInOrder(['Parcialmente paga', 'Valor pago', 'R$ 80,00', 'Saldo em aberto', 'R$ 120,00'])
            ->assertSeeTextInOrder(['Valor abatido nesta venda', (string) $payment->id, 'Pix', 'R$ 80,00']);

        $this->get(route('customers.show', $customer))->assertOk()->assertSeeTextInOrder(['Saldo em aberto', 'R$ 420,00']);
        $this->get(route('customers.show', [$customer, 'secao' => 'pagamentos']))->assertOk()->assertSee('Sinal');
    }

    public function test_full_payment_settles_the_sale_and_disables_new_payments(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '200.00', 'total' => '200.00']);
        $earlier = Payment::factory()->create(['customer_id' => $sale->customer_id, 'amount' => '50.00']);
        $earlier->allocations()->create(['sale_id' => $sale->id, 'amount' => '50.00']);

        $this->actingAs(User::factory()->create())
            ->get(route('sales.show', $sale))->assertOk()
            ->assertSee('max="150.00"', false)
            ->assertSeeText('O pagamento será destinado exclusivamente à venda #'.$sale->id.'.')
            ->assertDontSeeText('O pagamento abate primeiro a compra mais antiga.');

        $this->post(route('sales.payments.store', $sale), $this->paymentDetails('150.00'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_allocations', ['sale_id' => $sale->id, 'amount' => '150.00']);
        $this->get(route('sales.show', $sale))->assertOk()
            ->assertSeeTextInOrder(['Quitada', 'Valor pago', 'R$ 200,00', 'Saldo em aberto', 'R$ 0,00'])
            ->assertSee('data-dialog-open="sale-payment" disabled', false)
            ->assertSeeText('Esta venda está quitada.');
    }

    public function test_amounts_above_the_sale_balance_zero_or_negative_are_rejected_without_writes(): void
    {
        $customer = Customer::factory()->create();
        $sale = Sale::factory()->for($customer)->create(['subtotal' => '100.00', 'total' => '100.00']);
        Sale::factory()->for($customer)->create(['subtotal' => '500.00', 'total' => '500.00']);
        $this->actingAs(User::factory()->create())->from(route('sales.show', $sale));

        $this->post(route('sales.payments.store', $sale), $this->paymentDetails('100.01'))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHasErrors(['amount' => 'O pagamento não pode ultrapassar o saldo em aberto desta venda.']);

        foreach (['0', '0.00', '-10.00'] as $amount) {
            $this->post(route('sales.payments.store', $sale), $this->paymentDetails($amount))->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseEmpty('payments');
        $this->assertDatabaseEmpty('payment_allocations');
    }

    public function test_cancelled_sale_does_not_receive_payments(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '100.00', 'total' => '100.00', 'status' => 'cancelled']);

        $this->actingAs(User::factory()->create())->get(route('sales.show', $sale))->assertOk()
            ->assertSee('data-dialog-open="sale-payment" disabled', false);

        $this->post(route('sales.payments.store', $sale), $this->paymentDetails('10.00'))
            ->assertSessionHasErrors(['amount' => 'Vendas canceladas não recebem pagamentos.']);
        $this->assertDatabaseEmpty('payments');
    }

    public function test_repeated_submission_of_the_same_form_is_recorded_once(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '100.00', 'total' => '100.00']);
        $form = $this->paymentDetails('40.00');
        $this->actingAs(User::factory()->create());

        $this->post(route('sales.payments.store', $sale), $form)->assertSessionHasNoErrors();
        $this->post(route('sales.payments.store', $sale), $form)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_allocations', 1);
    }

    public function test_competing_payments_cannot_exceed_the_sale_balance_together(): void
    {
        $sale = Sale::factory()->create(['subtotal' => '100.00', 'total' => '100.00']);
        $this->actingAs(User::factory()->create());

        $this->post(route('sales.payments.store', $sale), $this->paymentDetails('100.00'))->assertSessionHasNoErrors();
        $this->post(route('sales.payments.store', $sale), $this->paymentDetails('100.00'))
            ->assertSessionHasErrors(['amount' => 'Esta venda já está quitada.']);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payment_allocations', ['sale_id' => $sale->id, 'amount' => '100.00']);
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
