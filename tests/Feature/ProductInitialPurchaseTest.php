<?php

namespace Tests\Feature;

use App\Actions\RegisterPurchase;
use App\Actions\RegisterSale;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class ProductInitialPurchaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    private Category $category;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->supplier = Supplier::factory()->create(['name' => 'Distribuidora Norte']);
        $this->actingAs($this->user);
    }

    public function test_product_without_initial_purchase_has_no_stock_movement_and_no_registered_cost(): void
    {
        $this->createProduct(['name' => 'Brinco'])->assertCreated()->assertJson(['message' => 'Produto cadastrado.']);

        $product = Product::query()->sole();
        $this->assertSame(0, $product->stock_quantity);
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('stock_movements');
        $this->assertFalse($product->hasRegisteredCost());
        $this->assertNull($product->profitMargin());
        $this->assertNull($product->grossProfit());

        $this->get(route('products.index'))->assertOk()
            ->assertSeeText('Sem custo registrado')->assertDontSeeText('100,00%');
    }

    public function test_initial_purchase_registers_a_confirmed_purchase_stock_and_average_cost(): void
    {
        $this->createProduct(['name' => 'Pulseira', 'sale_price' => '400.00'], ['quantity' => '5', 'unit_cost' => '250.00'])
            ->assertCreated()->assertJson(['message' => 'Produto cadastrado e compra inicial registrada no estoque.']);

        $product = Product::query()->sole();
        $purchase = Purchase::query()->with('items')->sole();
        $this->assertSame(Purchase::Confirmed, $purchase->status);
        $this->assertSame($this->supplier->id, $purchase->supplier_id);
        $this->assertSame('2026-11-20', $purchase->purchased_on->toDateString());
        $this->assertSame('1250.00', $purchase->total);
        $this->assertSame($product->id, $purchase->items->sole()->product_id);
        $this->assertSame(5, $product->stock_quantity);
        $this->assertSame('250.0000', $product->average_cost);
        $this->assertSame('150.00', (string) $product->grossProfit());
        $this->assertSame('37.50', $product->profitMargin());
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => StockMovement::PurchaseEntry, 'quantity_delta' => 5, 'unit_cost' => '250.0000']);

        $this->get(route('purchases.index'))->assertOk()->assertSee(route('purchases.show', $purchase))->assertSeeText('Distribuidora Norte');
        $this->get(route('products.index'))->assertOk()->assertSeeText('R$ 250,00')->assertSeeText('37,50%');
    }

    public function test_initial_purchase_discount_reduces_the_net_unit_cost(): void
    {
        $this->createProduct(['sale_price' => '10.00'], ['quantity' => '3', 'unit_cost' => '10.00', 'discount' => '10.00'])->assertCreated();

        $product = Product::query()->sole();
        $this->assertSame('20.00', Purchase::query()->sole()->total);
        $this->assertSame('6.6667', $product->average_cost);
        $this->assertSame('33.33', $product->profitMargin());
    }

    public function test_initial_purchase_requires_an_active_supplier_and_valid_amounts_and_persists_nothing_when_invalid(): void
    {
        $this->createProduct(['supplier_id' => null], ['quantity' => '1', 'unit_cost' => '10.00'])
            ->assertUnprocessable()->assertJsonValidationErrors(['supplier_id' => 'Selecione o fornecedor da compra inicial.']);

        $inactive = Supplier::factory()->create(['is_active' => false]);
        $this->createProduct(['supplier_id' => $inactive->id], ['quantity' => '1', 'unit_cost' => '10.00'])
            ->assertUnprocessable()->assertJsonValidationErrors(['supplier_id' => 'Selecione um fornecedor ativo para a compra inicial.']);

        $this->createProduct([], ['quantity' => '2', 'unit_cost' => '10.00', 'discount' => '20.01'])
            ->assertUnprocessable()->assertJsonValidationErrors(['discount' => 'O desconto não pode ultrapassar o valor bruto da compra (quantidade × custo unitário).']);

        $this->createProduct([], ['quantity' => '0', 'unit_cost' => '-1'])
            ->assertUnprocessable()->assertJsonValidationErrors(['quantity', 'unit_cost']);

        $this->assertDatabaseEmpty('products');
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_failure_while_registering_the_initial_purchase_rolls_back_the_product(): void
    {
        $this->mock(RegisterPurchase::class)->shouldReceive('handle')->once()->andThrow(new RuntimeException('Falha simulada'));
        $this->withoutExceptionHandling();

        try {
            $this->createProduct([], ['quantity' => '1', 'unit_cost' => '10.00']);
            $this->fail('A falha da compra inicial deveria interromper o cadastro.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }

        $this->assertDatabaseEmpty('products');
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_repeated_submission_of_the_same_form_creates_one_product_purchase_and_movement(): void
    {
        $token = (string) Str::uuid();

        $this->createProduct(['submission_token' => $token], ['quantity' => '2', 'unit_cost' => '10.00'])->assertCreated();
        $this->createProduct(['submission_token' => $token], ['quantity' => '2', 'unit_cost' => '10.00'])->assertCreated();
        $this->createProduct(['submission_token' => (string) Str::uuid(), 'name' => 'Outro produto'])->assertCreated();

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(2, Product::query()->where('submission_token', $token)->sole()->stock_quantity);
    }

    public function test_editing_a_product_cannot_register_an_initial_purchase(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $this->patchJson(route('products.update', $product), [
            'name' => 'Brinco', 'category_id' => $this->category->id, 'sale_price' => '10.00',
            'initial_purchase' => '1', 'quantity' => '1', 'unit_cost' => '1.00', 'purchased_on' => '2026-11-20', 'supplier_id' => $this->supplier->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['initial_purchase']);

        $this->assertDatabaseEmpty('purchases');
    }

    public function test_purchase_can_create_several_new_products_each_with_its_own_cost_and_stock(): void
    {
        $this->postPurchase([
            ['new_product' => $this->newProduct('Colar', '800.00'), 'quantity' => '2', 'unit_cost' => '550.00', 'discount' => '100.00'],
            ['new_product' => $this->newProduct('Anel', '90.00'), 'quantity' => '10', 'unit_cost' => '40.00'],
        ])->assertRedirect();

        $necklace = Product::query()->where('name', 'Colar')->sole();
        $ring = Product::query()->where('name', 'Anel')->sole();
        $this->assertSame([2, '500.0000', '37.50'], [$necklace->stock_quantity, $necklace->average_cost, $necklace->profitMargin()]);
        $this->assertSame([10, '40.0000', '55.56'], [$ring->stock_quantity, $ring->average_cost, $ring->profitMargin()]);
        $this->assertSame($this->supplier->id, $necklace->supplier_id);
        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_new_purchases_of_an_existing_product_update_the_weighted_average_cost(): void
    {
        $this->createProduct(['name' => 'Brinco', 'sale_price' => '400.00'], ['quantity' => '5', 'unit_cost' => '250.00'])->assertCreated();
        $product = Product::query()->sole();

        $this->postPurchase([['product_id' => $product->id, 'quantity' => '5', 'unit_cost' => '300.00']])->assertRedirect();

        $product->refresh();
        $this->assertSame(10, $product->stock_quantity);
        $this->assertSame('275.0000', $product->average_cost);
        $this->assertSame('31.25', $product->profitMargin());
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_price_below_cost_shows_a_negative_profit_and_margin(): void
    {
        $this->createProduct(['sale_price' => '200.00'], ['quantity' => '1', 'unit_cost' => '250.00'])->assertCreated();
        $product = Product::query()->sole();

        $this->assertSame('-50.00', (string) $product->grossProfit());
        $this->assertSame('-25.00', $product->profitMargin());
        $this->get(route('products.index'))->assertOk()->assertSeeText('-25,00%');
        $html = $this->editorHtml(route('products.edit', $product));
        $this->assertStringContainsString('-R$ 50,00', $html);
        $this->assertStringContainsString('<p class="commercial-summary-alert" data-summary-alert="loss" role="status" >', $html);
    }

    public function test_products_without_purchases_show_unknown_cost_everywhere(): void
    {
        $product = Product::factory()->for($this->category)->create(['name' => 'Sem compra', 'sale_price' => '50.00']);

        $this->get(route('products.index'))->assertOk()->assertSeeText('Sem custo registrado')->assertDontSeeText('100,00%');
        $this->get(route('products.index'))->assertOk()->assertSeeText('Sem custo registrado');
        $this->get(route('products.show', $product))->assertOk()->assertSeeText('Sem custo registrado');
        $html = $this->editorHtml(route('products.edit', $product));
        $this->assertStringContainsString('data-average-cost=""', $html);
        $this->assertStringContainsString('Sem custo registrado', $html);
        $this->assertStringNotContainsString('100,00%', $html);
    }

    public function test_later_purchases_do_not_change_costs_already_recorded_in_sales_or_movements(): void
    {
        $this->createProduct(['name' => 'Brinco', 'sale_price' => '400.00'], ['quantity' => '5', 'unit_cost' => '250.00'])->assertCreated();
        $product = Product::query()->sole();
        $sale = app(RegisterSale::class)->handle($this->user, [
            'customer_id' => Customer::factory()->create()->id,
            'sold_on' => '2026-11-21',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $this->postPurchase([['product_id' => $product->id, 'quantity' => '4', 'unit_cost' => '400.00']])->assertRedirect();

        $this->assertSame('250.0000', $sale->items()->sole()->unit_cost);
        $this->assertSame(['250.0000', '250.0000', '400.0000'], StockMovement::query()->orderBy('id')->pluck('unit_cost')->all());
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertSame('325.0000', $product->fresh()->average_cost);
    }

    public function test_repeated_purchase_submission_is_recorded_once(): void
    {
        $product = Product::factory()->for($this->category)->create();
        $items = [['product_id' => $product->id, 'quantity' => '3', 'unit_cost' => '5.00']];
        $token = (string) Str::uuid();

        $this->postPurchase($items, $token)->assertRedirect();
        $this->postPurchase($items, $token)->assertRedirect();

        $this->assertDatabaseCount('purchases', 1);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_drawer_and_purchase_form_offer_the_commercial_summary_without_duplicating_fields(): void
    {
        $html = $this->editorHtml(route('products.create'));
        foreach (['Registrar compra inicial deste produto', 'Resumo comercial', 'name="submission_token"', 'Estoque inicial'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->get(route('purchases.create'))->assertOk()
            ->assertSee('Resumo comercial')
            ->assertDontSee('Registrar compra inicial deste produto')
            ->assertDontSee('name="items[0][new_product][supplier_id]"', false)
            ->assertSee('name="submission_token"', false);
    }

    private function editorHtml(string $url): string
    {
        return $this->getJson($url)->assertOk()->json('html');
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  ?array<string, string>  $initialPurchase
     */
    private function createProduct(array $product = [], ?array $initialPurchase = null): TestResponse
    {
        return $this->postJson(route('products.store'), [
            'name' => 'Produto',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'sale_price' => '15.00',
            ...$product,
            ...($initialPurchase === null ? [] : ['initial_purchase' => '1', 'purchased_on' => '2026-11-20', ...$initialPurchase]),
        ]);
    }

    /** @param list<array<string, mixed>> $items */
    private function postPurchase(array $items, ?string $token = null): TestResponse
    {
        return $this->post(route('purchases.store'), array_filter([
            'supplier_id' => $this->supplier->id,
            'purchased_on' => '2026-11-22',
            'submission_token' => $token,
            'items' => $items,
        ]));
    }

    /** @return array{name: string, category_id: int, sale_price: string} */
    private function newProduct(string $name, string $salePrice): array
    {
        return ['name' => $name, 'category_id' => $this->category->id, 'sale_price' => $salePrice];
    }
}
