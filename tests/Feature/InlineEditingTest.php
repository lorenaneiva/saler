<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InlineEditingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_category_can_be_renamed_from_the_filtered_listing(): void
    {
        $category = Category::factory()->create(['name' => 'Bebidas']);
        $this->actingAs(User::factory()->create());

        $this->get(route('categories.index', ['q' => 'Bebidas']))->assertOk()
            ->assertSee('data-inline-editor', false)
            ->assertSee(route('categories.update', $category))
            ->assertDontSee(route('categories.edit', $category));

        $this->patchJson(route('categories.update', $category), ['name' => 'Sucos'])
            ->assertOk()->assertExactJson(['message' => 'Categoria atualizada.']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Sucos']);
    }

    public function test_supplier_can_be_updated_and_optional_representative_cleared_inline(): void
    {
        $supplier = Supplier::factory()->create(['representative' => 'Ana']);
        $this->actingAs(User::factory()->create());

        $this->get(route('suppliers.index'))->assertOk()
            ->assertSee(route('suppliers.update', $supplier))
            ->assertDontSee(route('suppliers.edit', $supplier));

        $this->patchJson(route('suppliers.update', $supplier), [
            'name' => 'Distribuidora Central', 'contact' => '91999990000', 'representative' => '',
        ])->assertOk()->assertExactJson(['message' => 'Fornecedor atualizado.']);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id, 'name' => 'Distribuidora Central', 'contact' => '91999990000', 'representative' => null,
        ]);
    }

    public function test_product_drawer_update_changes_catalog_fields_without_changing_stock_or_cost(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 8, 'average_cost' => '5.0000']);
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('products.index'))->assertOk()
            ->assertSee(route('products.edit', $product))
            ->assertSee('data-product-open', false)
            ->assertDontSee('data-inline-editor', false);

        $this->patchJson(route('products.update', $product), [
            'name' => 'Arroz integral', 'category_id' => $category->id, 'supplier_id' => $supplier->id,
            'sale_price' => '12.50', 'barcode' => '000123', 'stock_quantity' => 99, 'average_cost' => '1.00',
        ])->assertOk()->assertExactJson(['message' => 'Produto atualizado.']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id, 'name' => 'Arroz integral', 'category_id' => $category->id,
            'supplier_id' => $supplier->id, 'sale_price' => '12.50', 'barcode' => '000123',
            'stock_quantity' => 8, 'average_cost' => '5.0000',
        ]);
    }

    public function test_customer_details_can_be_edited_on_the_profile_and_escape_user_input(): void
    {
        $customer = Customer::factory()->create(['email' => 'old@example.test']);
        $this->actingAs(User::factory()->create());

        $this->get(route('customers.show', [$customer, 'secao' => 'compras']))->assertOk()
            ->assertSee(route('customers.update', $customer))
            ->assertDontSee(route('customers.edit', $customer));

        $this->patchJson(route('customers.update', $customer), [
            'name' => 'Maria <Silva>', 'phone' => '91999990000', 'email' => '',
            'cpf' => '01234567890', 'postal_code' => '66000-000',
        ])->assertOk()->assertExactJson(['message' => 'Cliente atualizado.']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id, 'name' => 'Maria <Silva>', 'phone' => '91999990000',
            'email' => null, 'cpf' => '01234567890', 'postal_code' => '66000-000',
        ]);
        $this->get(route('customers.show', $customer))->assertOk()
            ->assertSee('Maria &lt;Silva&gt;', false)->assertDontSee('Maria <Silva>', false);
    }

    public function test_customer_can_be_edited_from_the_listing_without_erasing_other_registration_details(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Maria Silva', 'email' => 'maria@example.test',
            'cpf' => '01234567890', 'postal_code' => '66000-000',
        ]);
        $otherCustomer = Customer::factory()->create(['name' => 'Maria Souza']);
        $this->actingAs(User::factory()->create());

        $this->get(route('customers.index', ['q' => 'Maria']))->assertOk()
            ->assertSee(route('customers.show', $customer))
            ->assertSee(route('customers.update', $customer))
            ->assertSee('form="customer-'.$customer->id.'"', false)
            ->assertSee('form="customer-'.$otherCustomer->id.'"', false)
            ->assertDontSee(route('customers.edit', $customer));

        $this->patchJson(route('customers.update', $customer), [
            'name' => 'Maria Santos', 'phone' => '91999990000', 'email' => '',
        ])->assertOk()->assertExactJson(['message' => 'Cliente atualizado.']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id, 'name' => 'Maria Santos', 'phone' => '91999990000',
            'email' => null, 'cpf' => '01234567890', 'postal_code' => '66000-000',
        ]);
        $this->assertDatabaseHas('customers', ['id' => $otherCustomer->id, 'name' => 'Maria Souza']);
    }

    /** @param class-string<Category|Supplier|Product|Customer> $model */
    #[DataProvider('editableResources')]
    public function test_invalid_inline_update_returns_field_errors_and_preserves_the_record(string $resource, string $model, string $message): void
    {
        $record = $model::factory()->create(['name' => 'Original']);

        $this->actingAs(User::factory()->create())
            ->patchJson(route($resource.'.update', $record), ['name' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['name' => $message]);

        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'name' => 'Original']);
    }

    /** @param class-string<Category|Supplier|Product|Customer> $model */
    #[DataProvider('editableResourceModels')]
    public function test_guest_inline_update_returns_401_without_changing_the_record(string $resource, string $model): void
    {
        $record = $model::factory()->create(['name' => 'Original']);

        $this->patchJson(route($resource.'.update', $record), ['name' => 'Alterado'])->assertUnauthorized();

        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'name' => 'Original']);
    }

    /** @return array<string, array{string, class-string}> */
    public static function editableResourceModels(): array
    {
        return array_map(fn (array $resource): array => array_slice($resource, 0, 2), self::editableResources());
    }

    /** @return array<string, array{string, class-string, string}> */
    public static function editableResources(): array
    {
        return [
            'categories' => ['categories', Category::class, 'Informe o nome da categoria.'],
            'suppliers' => ['suppliers', Supplier::class, 'Informe o campo nome.'],
            'products' => ['products', Product::class, 'Informe o nome do produto.'],
            'customers' => ['customers', Customer::class, 'Informe o campo nome.'],
        ];
    }
}
