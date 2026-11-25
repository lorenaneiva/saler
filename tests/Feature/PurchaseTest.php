<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_cannot_access_or_confirm_purchases(): void
    {
        $this->get(route('purchases.index'))->assertRedirectToRoute('login');
        $this->post(route('purchases.store'))->assertRedirectToRoute('login');
        $this->post(route('purchases.confirm', 1))->assertRedirectToRoute('login');
        $this->post(route('purchases.cancel', 1))->assertRedirectToRoute('login');
    }

    public function test_purchase_form_displays_even_without_suppliers_categories_or_products(): void
    {
        $this->actingAs(User::factory()->create())->get(route('purchases.create'))->assertOk()
            ->assertSee('Registrar compra')
            ->assertSee('Cadastre ou ative um fornecedor')
            ->assertSee('Cadastrar novo produto')
            ->assertSee('Cadastrar categoria')
            ->assertSee('Fornecedor <span aria-hidden="true" class="text-red-700">*</span>', false);
    }

    public function test_registering_a_purchase_preserves_costs_and_updates_stock_immediately(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['name' => 'Distribuidora Norte']);
        $rice = Product::factory()->create(['name' => 'Arroz', 'stock_quantity' => 2, 'average_cost' => '10.0000']);
        $beans = Product::factory()->create(['name' => 'Feijão']);

        $this->actingAs($user)->get(route('purchases.create'))->assertOk()
            ->assertSee('<select name="supplier_id"', false)
            ->assertSee('data-product-select', false)
            ->assertSee('Distribuidora Norte')->assertSee('Arroz');

        $response = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_on' => '2026-11-24',
            'items' => [
                ['product_id' => $rice->id, 'quantity' => 3, 'unit_cost' => '20.00', 'discount' => '6.00'],
                ['product_id' => $beans->id, 'quantity' => 2, 'unit_cost' => '7.25'],
            ],
        ]);
        $purchase = Purchase::query()->sole();

        $response->assertRedirectToRoute('purchases.show', $purchase);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id, 'status' => Purchase::Confirmed, 'total' => '68.50', 'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id, 'product_id' => $rice->id, 'unit_cost' => '20.00', 'discount' => '6.00', 'total' => '54.00',
        ]);
        $this->get(route('purchases.index'))->assertOk()->assertSee('Distribuidora Norte');
        $this->get(route('purchases.show', $purchase))->assertOk()->assertSee('R$ 68,50')->assertSee('Confirmada');
        $this->assertDatabaseHas('products', ['id' => $rice->id, 'stock_quantity' => 5, 'average_cost' => '14.8000']);
        $this->assertDatabaseHas('products', ['id' => $beans->id, 'stock_quantity' => 2, 'average_cost' => '7.2500']);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $rice->id, 'quantity_delta' => 3, 'unit_cost' => '18.0000', 'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->post(route('purchases.confirm', $purchase))->assertRedirectToRoute('purchases.show', $purchase);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->post(route('purchases.cancel', $purchase))->assertSessionHasErrors('purchase');
    }

    public function test_cancelling_a_pending_purchase_prevents_confirmation_and_stock_entry(): void
    {
        $purchase = Purchase::factory()->create();
        $product = Product::factory()->create();

        $purchase->items()->create([
            'product_id' => $product->id, 'quantity' => 5, 'unit_cost' => '10.00', 'discount' => '0.00', 'total' => '50.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('purchases.cancel', $purchase))->assertRedirectToRoute('purchases.show', $purchase);
        $this->from(route('purchases.show', $purchase))
            ->post(route('purchases.confirm', $purchase))
            ->assertRedirectToRoute('purchases.show', $purchase)
            ->assertSessionHasErrors('purchase');

        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'status' => Purchase::Cancelled]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_purchase_can_create_the_first_product_with_stock(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $category = Category::factory()->create(['name' => 'Mercearia']);

        $this->actingAs($user)->get(route('purchases.create'))->assertOk()
            ->assertSee('Cadastrar novo produto')
            ->assertSee('data-new-product-field="category_id"', false)
            ->assertSee('Mercearia');

        $response = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_on' => '2026-11-24',
            'items' => [
                [
                    'new_product' => [
                        'name' => 'Farinha',
                        'category_id' => $category->id,
                        'sale_price' => '12.50',
                        'barcode' => '0012345',
                    ],
                    'quantity' => 4,
                    'unit_cost' => '8.00',
                ],
            ],
        ]);
        $purchase = Purchase::query()->sole();
        $product = Product::query()->sole();

        $response->assertRedirectToRoute('purchases.show', $purchase);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Farinha',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'sale_price' => '12.50',
            'barcode' => '0012345',
            'stock_quantity' => 4,
            'average_cost' => '8.0000',
        ]);
        $this->assertDatabaseHas('purchase_items', ['purchase_id' => $purchase->id, 'product_id' => $product->id]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity_delta' => 4]);
    }

    public function test_invalid_new_product_preserves_form_values_without_creating_records(): void
    {
        $supplier = Supplier::factory()->create();
        Category::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('purchases.create'))
            ->followingRedirects()
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchased_on' => '2026-11-24',
                'items' => [
                    [
                        'new_product' => ['name' => 'Farinha', 'category_id' => 999],
                        'quantity' => 4,
                        'unit_cost' => '8.00',
                    ],
                ],
            ])->assertOk()
            ->assertSee('Selecione uma categoria válida para o novo produto.')
            ->assertSee('Informe um preço de venda válido para o novo produto')
            ->assertSee('value="Farinha"', false);

        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('products');
    }

    public function test_stock_failure_rolls_back_the_purchase_and_its_new_product(): void
    {
        $supplier = Supplier::factory()->create();
        $category = Category::factory()->create();
        $fullProduct = Product::factory()->create(['stock_quantity' => 2147483647]);

        $this->actingAs(User::factory()->create())
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchased_on' => '2026-11-24',
                'items' => [
                    [
                        'new_product' => ['name' => 'Farinha', 'category_id' => $category->id, 'sale_price' => '12.50'],
                        'quantity' => 4,
                        'unit_cost' => '8.00',
                    ],
                    ['product_id' => $fullProduct->id, 'quantity' => 1, 'unit_cost' => '5.00'],
                ],
            ])
            ->assertRedirectToRoute('purchases.create')
            ->assertSessionHasErrors(['purchase' => 'O saldo de estoque ultrapassaria o limite permitido.']);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $fullProduct->id, 'stock_quantity' => 2147483647]);
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('purchase_items');
        $this->assertDatabaseEmpty('stock_movements');
    }

    public function test_excessive_discounts_do_not_create_a_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('purchases.create'))
            ->followingRedirects()
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchased_on' => '2026-11-24',
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => '5.00', 'discount' => '10.01']],
            ])->assertOk()
            ->assertSee('O desconto não pode ultrapassar o subtotal do item.')
            ->assertSee('value="10.01"', false);
        $this->assertDatabaseEmpty('purchases');
        $this->assertDatabaseEmpty('purchase_items');
    }
}
