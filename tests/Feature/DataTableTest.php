<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_every_listing_uses_the_shared_table_container_with_live_search_and_no_search_button(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['sales.index', 'purchases.index', 'customers.index', 'suppliers.index', 'products.index', 'categories.index', 'stock.index', 'stock.reasons.index'] as $routeName) {
            $this->get(route($routeName))->assertOk()
                ->assertSee('panel table-section', false)->assertSee('data-live-table', false)
                ->assertSee('data-live-search', false)
                ->assertSee('class="table-pagination"', false)
                ->assertDontSee('>Buscar</button>', false)
                ->assertDontSee('filter-bar', false);
        }
    }

    public function test_single_page_keeps_both_arrows_visible_but_disabled_and_locks_the_page_input(): void
    {
        Customer::factory()->create(['name' => 'Ana']);

        $this->actingAs(User::factory()->create())->get(route('customers.index'))->assertOk()
            ->assertSeeText('1 cliente')
            ->assertSeeText('1–1 de 1')
            ->assertSee('<span class="pagination-step" aria-disabled="true" aria-label="Página anterior">', false)
            ->assertSee('<span class="pagination-step" aria-disabled="true" aria-label="Próxima página">', false)
            ->assertSee('min="1" max="1"', false)
            ->assertSee('data-current-page="1" disabled', false)
            ->assertSeeText('de 1');
    }

    public function test_middle_page_links_both_arrows_and_keeps_filters_in_the_page_jump_form(): void
    {
        Customer::factory()->count(45)->create(['name' => 'Cliente Silva']);

        $this->actingAs(User::factory()->create())->get(route('customers.index', ['q' => 'Silva', 'page' => 2]))->assertOk()
            ->assertSeeText('45 clientes')
            ->assertSeeText('16–30 de 45')
            ->assertSee('href="'.e(route('customers.index', ['q' => 'Silva', 'page' => 1])).'" rel="prev" aria-label="Página anterior"', false)
            ->assertSee('href="'.e(route('customers.index', ['q' => 'Silva', 'page' => 3])).'" rel="next" aria-label="Próxima página"', false)
            ->assertSee('<input type="hidden" name="q" value="Silva">', false)
            ->assertSee('name="page" value="2" min="1" max="3"', false);
    }

    public function test_search_forms_and_clear_links_drop_the_current_page_so_filtering_restarts_on_page_one(): void
    {
        Customer::factory()->count(25)->create(['name' => 'Cliente Silva']);

        $response = $this->actingAs(User::factory()->create())->get(route('customers.index', ['q' => 'Silva', 'page' => 2]))->assertOk();

        $searchForm = str($response->getContent())->betweenFirst('id="q-search"', '</form>');
        $this->assertStringNotContainsString('name="page"', $searchForm);
        $this->assertStringContainsString('href="'.route('customers.index').'" class="button-quiet clear-filters"', $searchForm);
    }

    public function test_money_columns_are_right_aligned_and_payment_status_uses_shared_badges(): void
    {
        Sale::factory()->create(['subtotal' => '15.00', 'total' => '15.00']);

        $this->actingAs(User::factory()->create())->get(route('sales.index'))->assertOk()
            ->assertSee('<th scope="col" class="sortable money">', false)
            ->assertSee('<td class="money">R$ 15,00', false)
            ->assertSee('<span class="badge badge-danger">Pendente</span>', false);
    }

    public function test_short_pages_are_completed_with_blank_rows_only_when_there_is_more_than_one_page(): void
    {
        Customer::factory()->count(20)->create();
        $this->actingAs(User::factory()->create());

        $lastPage = $this->get(route('customers.index', ['page' => 2]))->assertOk()->assertSeeText('16–20 de 20')->getContent();
        $this->assertSame(10, substr_count(str($lastPage)->betweenFirst('<tbody class="table-fillers" aria-hidden="true">', '</tbody>'), '<tr>'));

        $emptyPage = $this->get(route('customers.index', ['page' => 9]))->assertOk()->getContent();
        $this->assertSame(14, substr_count(str($emptyPage)->betweenFirst('<tbody class="table-fillers" aria-hidden="true">', '</tbody>'), '<tr>'));

        $this->get(route('customers.index', ['q' => Customer::query()->value('name')]))->assertOk()->assertDontSee('table-fillers', false);
    }
}
