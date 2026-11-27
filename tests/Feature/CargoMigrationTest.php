<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CargoMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** The three Security migrations, newest first, as `migrate:rollback --step=3` undoes them. */
    private const SecurityMigrations = 3;

    public function test_an_empty_database_gets_both_roles_and_no_users(): void
    {
        $this->assertSame(['administrador' => 'Administrador', 'root' => 'Root'], Cargo::query()->orderBy('slug')->pluck('name', 'slug')->all());
        $this->assertDatabaseCount('users', 0);
        $this->assertFalse(collect(Schema::getColumns('users'))->firstWhere('name', 'cargo_id')['nullable']);
    }

    public function test_an_existing_database_keeps_every_user_and_commercial_record_and_users_become_administrators(): void
    {
        $sale = Sale::factory()->create();
        $sale->items()->create(['product_id' => Product::factory()->create()->id, 'quantity' => 1, 'unit_price' => '15.00', 'unit_cost' => '1.00', 'subtotal' => '15.00']);
        Purchase::factory()->create();
        Payment::factory()->create(['customer_id' => $sale->customer_id])->allocations()->create(['sale_id' => $sale->id, 'amount' => '5.00']);
        $counts = fn (): array => [
            'products' => Product::query()->count(), 'sales' => Sale::query()->count(), 'purchases' => Purchase::query()->count(),
            'payments' => Payment::query()->count(), 'customers' => Customer::query()->count(), 'sale_items' => DB::table('sale_items')->count(),
            'payment_allocations' => DB::table('payment_allocations')->count(),
        ];
        $before = $counts();

        $this->artisan('migrate:rollback', ['--step' => self::SecurityMigrations])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('cargos'));
        $this->assertFalse(Schema::hasColumn('users', 'cargo_id'));
        DB::table('users')->insert(['name' => 'Usuária antiga', 'email' => 'antiga@exemplo.com', 'password' => Hash::make('senha-antiga-123'), 'created_at' => now(), 'updated_at' => now()]);
        $usersBefore = DB::table('users')->orderBy('id')->get(['id', 'name', 'email', 'password'])->map(fn ($user) => (array) $user)->all();

        $this->artisan('migrate')->assertSuccessful();

        $this->assertSame($before, $counts());
        $this->assertSame($usersBefore, DB::table('users')->orderBy('id')->get(['id', 'name', 'email', 'password'])->map(fn ($user) => (array) $user)->all());
        $administrador = Cargo::idFor(Cargo::Administrador);
        $this->assertSame(0, User::query()->where('cargo_id', '!=', $administrador)->count());
        $this->assertSame(0, User::query()->where('ativo', false)->orWhere('must_change_password', true)->count());

        $legacy = User::query()->where('email', 'antiga@exemplo.com')->sole();
        $this->post(route('login.store'), ['email' => 'antiga@exemplo.com', 'password' => 'senha-antiga-123'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($legacy);
        $this->get(route('sales.index'))->assertOk();
        $this->get(route('security.users.index'))->assertForbidden();
    }

    public function test_the_role_data_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_11_27_142955_insert_default_cargos.php');
        $ids = Cargo::query()->orderBy('id')->pluck('id', 'slug')->all();

        $migration->up();
        $migration->up();

        $this->assertSame($ids, Cargo::query()->orderBy('id')->pluck('id', 'slug')->all());
    }

    public function test_the_database_refuses_a_second_root_even_when_application_checks_are_bypassed(): void
    {
        User::factory()->root()->create();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('users_single_root_unique');

        User::factory()->root()->create();
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        User::factory()->create();

        $this->expectException(QueryException::class);

        Cargo::query()->where('slug', Cargo::Administrador)->delete();
    }
}
