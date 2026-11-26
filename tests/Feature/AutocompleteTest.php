<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AutocompleteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_search_ignores_case_and_accents_and_returns_only_option_data(): void
    {
        $customer = Customer::factory()->create(['name' => 'Márcia Conceição', 'email' => 'private@example.test']);
        Customer::factory()->create(['name' => 'Outro cliente']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('autocomplete.options', ['source' => 'customers', 'q' => 'MARCIA CONCEICAO']))
            ->assertOk()->assertExactJson([
                'options' => [['value' => (string) $customer->id, 'label' => 'Márcia Conceição']],
                'next_page' => null,
            ]);
    }

    public function test_product_search_preserves_sale_and_purchase_availability_rules_and_searches_barcodes(): void
    {
        $available = Product::factory()->create(['name' => 'Feijão', 'barcode' => '000123', 'stock_quantity' => 8, 'sale_price' => '12.50']);
        $empty = Product::factory()->create(['name' => 'Feijão sem estoque', 'stock_quantity' => 0]);
        Product::factory()->create(['name' => 'Feijão inativo', 'is_active' => false, 'stock_quantity' => 8]);
        $this->actingAs(User::factory()->create());

        $this->getJson(route('autocomplete.options', ['source' => 'sale-products', 'q' => 'FEIJAO']))
            ->assertOk()->assertExactJson([
                'options' => [['value' => (string) $available->id, 'label' => 'Feijão · R$ 12,50 · 8 em estoque']],
                'next_page' => null,
            ]);
        $this->getJson(route('autocomplete.options', ['source' => 'sale-products', 'q' => '000123']))
            ->assertOk()->assertJsonPath('options.0.value', (string) $available->id);
        $this->getJson(route('autocomplete.options', ['source' => 'purchase-products', 'q' => 'feijão']))
            ->assertOk()->assertJsonCount(2, 'options')->assertJsonFragment(['value' => (string) $empty->id]);
    }

    public function test_search_matches_decomposed_unicode_accents(): void
    {
        $customer = Customer::factory()->create(['name' => "Cafe\u{0301} Conceição"]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('autocomplete.options', ['source' => 'customers', 'q' => 'CAFÉ CONCEICAO']))
            ->assertOk()->assertJsonCount(1, 'options')->assertJsonPath('options.0.value', (string) $customer->id);
    }

    public function test_results_are_paginated_and_wildcard_characters_are_treated_as_text(): void
    {
        Customer::factory()->count(31)->create(['name' => 'Cliente 10%']);
        Customer::factory()->create(['name' => 'Cliente 100']);
        $this->actingAs(User::factory()->create());

        $this->getJson(route('autocomplete.options', ['source' => 'customers', 'q' => '10%']))
            ->assertOk()->assertJsonCount(30, 'options')->assertJsonPath('next_page', 2);
        $this->getJson(route('autocomplete.options', ['source' => 'customers', 'q' => '10%', 'page' => 2]))
            ->assertOk()->assertJsonCount(1, 'options')->assertJsonPath('next_page', null);
        $this->getJson(route('autocomplete.options', ['source' => 'customers', 'q' => 'inexistente']))
            ->assertOk()->assertExactJson(['options' => [], 'next_page' => null]);
    }

    public function test_operational_forms_limit_initial_options_but_keep_previous_selections(): void
    {
        Customer::factory()->count(35)->create(['name' => 'Cliente A']);
        $customer = Customer::factory()->create(['name' => 'Zélia']);
        Product::factory()->count(35)->create(['name' => 'Produto A', 'stock_quantity' => 1]);
        $product = Product::factory()->create(['name' => 'Produto Z', 'stock_quantity' => 1]);
        Supplier::factory()->create();
        $this->actingAs(User::factory()->create())->withSession(['_old_input' => [
            'customer_id' => $customer->id, 'items' => [['product_id' => $product->id]],
        ]]);

        $this->get(route('sales.create'))->assertOk()
            ->assertViewHas('customers', fn ($options): bool => $options->count() === 31 && $options->contains($customer))
            ->assertViewHas('products', fn ($options): bool => $options->count() === 31 && $options->contains($product))
            ->assertSee('Zélia')->assertSee('Produto Z')->assertSee('data-autocomplete-url', false);
        $this->withSession(['_old_input' => ['items' => [['product_id' => $product->id]]]])
            ->get(route('purchases.create'))->assertOk()
            ->assertViewHas('products', fn ($options): bool => $options->count() === 31 && $options->contains($product));
    }

    public function test_search_requires_authentication_and_rejects_unknown_sources(): void
    {
        $this->getJson(route('autocomplete.options', 'customers'))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson(route('autocomplete.options', 'users'))->assertNotFound();
    }
}
