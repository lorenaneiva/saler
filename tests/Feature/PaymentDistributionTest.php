<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentDistributionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
        $this->actingAs(User::factory()->create());
    }

    public function test_payment_settles_a_single_purchase(): void
    {
        $sale = $this->sale('80.00');
        $this->sale('300.00');

        $this->pay('80.00')->assertSessionHasNoErrors();

        $this->assertAllocations([$sale->id => '80.00']);
        $this->assertSame('Quitada', $sale->fresh()->loadSum('paymentAllocations as paid_total', 'amount')->paymentStatus());
    }

    public function test_payment_settles_smallest_balances_first_and_partially_pays_the_next_one(): void
    {
        $first = $this->sale('1000.00', '2026-11-01');
        $second = $this->sale('200.00', '2026-11-02');
        $third = $this->sale('150.00', '2026-11-03');

        $this->preview('400.00')->assertOk()
            ->assertSeeTextInOrder(['#'.$third->id, 'Quitada', 'R$ 150,00', 'R$ 150,00', 'R$ 0,00'])
            ->assertSeeTextInOrder(['#'.$second->id, 'Quitada', 'R$ 200,00', 'R$ 200,00', 'R$ 0,00'])
            ->assertSeeTextInOrder(['#'.$first->id, 'Parcial', 'R$ 1.000,00', 'R$ 50,00', 'R$ 950,00'])
            ->assertSeeTextInOrder(['Total do pagamento', 'R$ 400,00', 'Compras que serão quitadas', '2', 'Compras com pagamento parcial', '1', 'Saldo do cliente após o pagamento', 'R$ 950,00']);

        $this->pay('400.00')->assertSessionHasNoErrors();

        $this->assertAllocations([$third->id => '150.00', $second->id => '200.00', $first->id => '50.00']);
    }

    public function test_equal_balances_go_to_the_oldest_sale_and_then_to_the_lowest_id(): void
    {
        $newest = $this->sale('100.00', '2026-11-10');
        $sameDayFirst = $this->sale('100.00', '2026-11-05');
        $sameDaySecond = $this->sale('100.00', '2026-11-05');

        $this->pay('250.00')->assertSessionHasNoErrors();

        $this->assertAllocations([$sameDayFirst->id => '100.00', $sameDaySecond->id => '100.00', $newest->id => '50.00']);
    }

    public function test_current_balance_after_earlier_partial_payments_decides_the_order(): void
    {
        $mostlyPaid = $this->sale('1000.00', '2026-11-10');
        $earlier = Payment::factory()->create(['customer_id' => $this->customer->id, 'amount' => '950.00']);
        $earlier->allocations()->create(['sale_id' => $mostlyPaid->id, 'amount' => '950.00']);
        $untouched = $this->sale('100.00', '2026-11-01');

        $this->pay('60.00')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $earlier->id, 'sale_id' => $mostlyPaid->id, 'amount' => '950.00']);
        $this->assertAllocations([$mostlyPaid->id => '50.00', $untouched->id => '10.00']);
    }

    public function test_paying_the_whole_debt_settles_every_open_sale_and_ignores_cancelled_or_settled_ones(): void
    {
        $sales = [$this->sale('10.00'), $this->sale('20.50'), $this->sale('30.25')];
        $cancelled = $this->sale('500.00', status: 'cancelled');
        $settled = $this->sale('40.00');
        $settledPayment = Payment::factory()->create(['customer_id' => $this->customer->id, 'amount' => '40.00']);
        $settledPayment->allocations()->create(['sale_id' => $settled->id, 'amount' => '40.00']);

        $this->preview('60.75')->assertOk()->assertDontSeeText('#'.$cancelled->id)->assertDontSeeText('#'.$settled->id)
            ->assertSeeTextInOrder(['Compras que serão quitadas', '3', 'Saldo do cliente após o pagamento', 'R$ 0,00']);
        $this->pay('60.75')->assertSessionHasNoErrors();

        $this->assertAllocations([$sales[0]->id => '10.00', $sales[1]->id => '20.50', $sales[2]->id => '30.25']);
        $this->get(route('customers.show', $this->customer))->assertSeeTextInOrder(['Saldo em aberto', 'R$ 0,00']);
    }

    public function test_payment_above_the_customer_debt_zero_or_negative_is_rejected_without_writes(): void
    {
        $this->sale('100.00');

        $this->preview('100.01')->assertOk()->assertSeeText('O valor informado supera o saldo em aberto do cliente')->assertDontSee('distribution_signature', false);
        $this->pay('100.01')->assertSessionHasErrors(['amount' => 'O pagamento não pode ultrapassar o saldo em aberto do cliente.']);
        foreach (['0', '-5.00'] as $amount) {
            $this->preview($amount)->assertOk()->assertSeeText('Informe o valor recebido');
            $this->pay($amount)->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseEmpty('payments');
        $this->assertDatabaseEmpty('payment_allocations');
    }

    public function test_registered_distribution_matches_the_preview_and_the_preview_never_writes(): void
    {
        $large = $this->sale('300.00');
        $small = $this->sale('120.00');

        $signature = $this->signatureFrom($this->preview('200.00'));
        $this->assertDatabaseEmpty('payments');

        $this->pay('200.00', $signature)->assertSessionHasNoErrors();

        $this->assertAllocations([$small->id => '120.00', $large->id => '80.00']);
    }

    public function test_preview_made_stale_by_a_concurrent_payment_requires_a_new_confirmation(): void
    {
        $large = $this->sale('300.00');
        $small = $this->sale('120.00');
        $staleSignature = $this->signatureFrom($this->preview('200.00'));

        $this->post(route('sales.payments.store', $small), $this->details('100.00'))->assertSessionHasNoErrors();

        $this->from(route('customers.show', $this->customer))->pay('200.00', $staleSignature)
            ->assertRedirect(route('customers.show', $this->customer))
            ->assertSessionHasErrors(['distribution_signature' => 'Os saldos das compras foram alterados por outra operação. Revise a nova distribuição e confirme o pagamento novamente.']);
        $this->assertDatabaseCount('payments', 1);

        $freshSignature = $this->signatureFrom($this->preview('200.00')->assertSeeTextInOrder(['#'.$small->id, 'R$ 20,00', 'R$ 20,00']));
        $this->pay('200.00', $freshSignature)->assertSessionHasNoErrors();

        $payment = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame([$small->id => '20.00', $large->id => '180.00'], $payment->allocations()->pluck('amount', 'sale_id')->all());
    }

    public function test_payment_registered_on_a_sale_still_goes_only_to_that_sale(): void
    {
        $large = $this->sale('300.00');
        $this->sale('50.00');

        $this->post(route('sales.payments.store', $large), $this->details('100.00'))->assertSessionHasNoErrors();

        $this->assertAllocations([$large->id => '100.00']);
    }

    public function test_customer_drawer_shows_the_distribution_section_and_new_rule_message(): void
    {
        $this->sale('100.00');

        $this->get(route('customers.show', $this->customer))->assertOk()
            ->assertSeeText('Distribuição do pagamento')
            ->assertSeeText('O pagamento será distribuído automaticamente, priorizando a quitação das compras com menor saldo em aberto.')
            ->assertSeeText('Informe o valor recebido para ver como o pagamento será distribuído.')
            ->assertSee('data-payment-distribution="'.route('customers.payments.preview', $this->customer).'"', false)
            ->assertDontSeeText('O pagamento abate primeiro a compra mais antiga.');
    }

    private function sale(string $total, string $soldOn = '2026-11-01', string $status = Sale::Completed): Sale
    {
        return Sale::factory()->for($this->customer)->create(['sold_on' => $soldOn, 'subtotal' => $total, 'total' => $total, 'status' => $status]);
    }

    private function preview(string $amount): TestResponse
    {
        return $this->get(route('customers.payments.preview', [$this->customer, 'amount' => $amount]));
    }

    private function pay(string $amount, ?string $signature = null): TestResponse
    {
        return $this->post(route('customers.payments.store', $this->customer), $this->details($amount) + array_filter(['distribution_signature' => $signature]));
    }

    /** @return array{submission_token: string, amount: string, paid_on: string, method: string} */
    private function details(string $amount): array
    {
        return ['submission_token' => (string) Str::uuid(), 'amount' => $amount, 'paid_on' => '2026-11-24', 'method' => 'pix'];
    }

    private function signatureFrom(TestResponse $response): string
    {
        preg_match('/name="distribution_signature" value="([0-9a-f]{40})"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'A prévia deveria trazer a assinatura da distribuição.');

        return $matches[1];
    }

    /** @param array<int, string> $expected */
    private function assertAllocations(array $expected): void
    {
        $payment = Payment::query()->latest('id')->firstOrFail();
        $this->assertSame($expected, $payment->allocations()->orderBy('id')->pluck('amount', 'sale_id')->all());
    }
}
