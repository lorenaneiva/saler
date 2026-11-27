<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityUsersTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = User::factory()->root()->create(['name' => 'Dona Root', 'email' => 'root@exemplo.com']);
    }

    /** @return array<string, string> */
    private function newUser(array $overrides = []): array
    {
        return [
            'name' => 'Nova Admin', 'email' => 'Nova@Exemplo.com', 'cargo' => Cargo::Administrador, 'situacao' => 'ativo',
            'password' => 'senha-inicial-1', 'password_confirmation' => 'senha-inicial-1', ...$overrides,
        ];
    }

    public function test_only_root_sees_and_opens_the_security_module(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($this->root)->get(route('dashboard'))->assertOk()
            ->assertSeeText('Segurança')->assertSee('href="'.route('security.users.index').'"', false);
        $this->get(route('security.users.index'))->assertOk()->assertSeeText('Usuários cadastrados');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertDontSeeText('Segurança')->assertDontSee(route('security.users.index'), false);
    }

    public function test_administrators_get_403_on_every_security_route(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($admin);

        $this->get(route('security.users.index'))->assertForbidden();
        $this->post(route('security.users.store'), $this->newUser())->assertForbidden();
        $this->patch(route('security.users.update', $other), ['name' => 'X', 'email' => 'x@exemplo.com'])->assertForbidden();
        $this->patch(route('security.users.deactivate', $other))->assertForbidden();
        $this->patch(route('security.users.activate', $other))->assertForbidden();
        $this->put(route('security.users.password', $other), ['password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123'])->assertForbidden();
        $this->patch(route('security.users.update', $admin), ['name' => 'Eu', 'email' => $admin->email])->assertForbidden();

        $this->assertDatabaseCount('users', 3);
        $this->assertTrue($other->fresh()->ativo);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('security.users.index'))->assertRedirectToRoute('login');
        $this->post(route('security.users.store'), $this->newUser())->assertRedirectToRoute('login');
    }

    public function test_listing_shows_columns_and_supports_search_filters_and_sorting(): void
    {
        $this->travelTo('2026-11-20 09:00');
        $ana = User::factory()->create(['name' => 'Ana Lima', 'email' => 'ana@exemplo.com']);
        $this->travelBack();
        $ana->forceFill(['last_login_at' => '2026-11-26 14:35:00'])->save();
        User::factory()->inactive()->create(['name' => 'Bruno Sá', 'email' => 'bruno@exemplo.com']);
        $this->actingAs($this->root);

        $this->get(route('security.users.index'))->assertOk()
            ->assertSeeInOrder(['ID', 'Nome', 'E-mail', 'Cargo', 'Situação', 'Cadastro', 'Último acesso', 'Ações'])
            ->assertSeeInOrder(['Ana Lima', 'ana@exemplo.com', 'Administrador', 'Ativo', '20/11/2026', '26/11/2026', '14:35', 'Redefinir senha'])
            ->assertSeeInOrder(['Bruno Sá', 'Inativo', 'Nunca acessou', 'Reativar'])
            ->assertSeeInOrder(['Dona Root', 'Root', 'Conta protegida'])
            ->assertDontSee('$2y$', false);

        $this->get(route('security.users.index', ['q' => 'BRUNO']))->assertSeeText('Bruno Sá')->assertDontSeeText('Ana Lima');
        $this->get(route('security.users.index', ['q' => 'ana@ex']))->assertSeeText('Ana Lima')->assertDontSeeText('Bruno Sá');
        $this->get(route('security.users.index', ['cargo' => 'root']))->assertSeeText('Dona Root')->assertDontSeeText('Ana Lima');
        $this->get(route('security.users.index', ['situacao' => 'inativos']))->assertSeeText('Bruno Sá')->assertDontSeeText('Ana Lima');
        $this->get(route('security.users.index', ['ordem' => '-email']))->assertSeeTextInOrder(['root@exemplo.com', 'bruno@exemplo.com', 'ana@exemplo.com']);
        foreach (['id', 'nome', 'email', 'cargo', 'situacao', 'cadastro', 'acesso'] as $column) {
            $this->get(route('security.users.index', ['ordem' => '-'.$column]))->assertOk()->assertSee('aria-sort="descending"', false);
        }
    }

    public function test_root_registers_an_administrator_who_must_change_the_initial_password(): void
    {
        $this->actingAs($this->root)->post(route('security.users.store'), $this->newUser())
            ->assertRedirectToRoute('security.users.index')->assertSessionHas('status');

        $user = User::query()->where('email', 'nova@exemplo.com')->sole();
        $this->assertSame([Cargo::Administrador, true, true], [$user->cargo->slug, $user->ativo, $user->must_change_password]);
        $this->assertTrue(Hash::check('senha-inicial-1', $user->password));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'nova@exemplo.com', 'password' => 'senha-inicial-1']);
        $this->get(route('sales.index'))->assertRedirectToRoute('password.edit');
    }

    public function test_registration_can_start_inactive_and_validates_with_the_password_policy(): void
    {
        $this->actingAs($this->root);

        $this->post(route('security.users.store'), $this->newUser(['situacao' => 'inativo']))->assertRedirect();
        $this->assertFalse(User::query()->where('email', 'nova@exemplo.com')->sole()->ativo);

        $this->from(route('security.users.index'))->post(route('security.users.store'), $this->newUser(['email' => 'nova@exemplo.com', 'password' => 'curta', 'password_confirmation' => 'outra']))
            ->assertRedirect(route('security.users.index'))
            ->assertSessionHasErrorsIn('createUser', ['email', 'password']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_root_cannot_be_created_or_granted_through_the_interface(): void
    {
        $this->actingAs($this->root);

        $this->post(route('security.users.store'), $this->newUser(['cargo' => Cargo::Root]))
            ->assertSessionHasErrorsIn('createUser', ['cargo']);
        $this->post(route('security.users.store'), $this->newUser(['email' => 'outra@exemplo.com', 'cargo_id' => Cargo::idFor(Cargo::Root)]))->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'nova@exemplo.com']);
        $this->assertSame(1, User::query()->where('cargo_id', Cargo::idFor(Cargo::Root))->count());
        $this->assertSame(Cargo::Administrador, User::query()->where('email', 'outra@exemplo.com')->sole()->cargo->slug);
    }

    public function test_root_edits_name_and_email_inline_but_role_status_and_password_fields_are_ignored(): void
    {
        $admin = User::factory()->create(['email' => 'admin@exemplo.com']);
        $password = $admin->password;

        $this->actingAs($this->root)->patchJson(route('security.users.update', $admin), [
            'name' => 'Admin Renomeada', 'email' => 'NOVO@exemplo.com',
            'cargo_id' => Cargo::idFor(Cargo::Root), 'cargo' => 'root', 'ativo' => false, 'must_change_password' => true, 'password' => 'invasao-12345',
        ])->assertOk()->assertExactJson(['message' => 'Usuária atualizada.']);

        $admin->refresh();
        $this->assertSame(['Admin Renomeada', 'novo@exemplo.com', Cargo::Administrador, true, false, $password],
            [$admin->name, $admin->email, $admin->cargo->slug, $admin->ativo, $admin->must_change_password, $admin->password]);

        $this->patchJson(route('security.users.update', $admin), ['name' => 'X', 'email' => 'root@exemplo.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_root_account_is_protected_from_the_interface(): void
    {
        $this->actingAs($this->root);

        $this->patch(route('security.users.deactivate', $this->root))->assertForbidden();
        $this->patch(route('security.users.update', $this->root), ['name' => 'X', 'email' => 'x@exemplo.com'])->assertForbidden();
        $this->put(route('security.users.password', $this->root), ['password' => 'senha-nova-123', 'password_confirmation' => 'senha-nova-123'])->assertForbidden();

        $this->root->refresh();
        $this->assertSame(['Dona Root', true, true], [$this->root->name, $this->root->ativo, $this->root->isRoot()]);
    }

    public function test_deactivating_signs_the_user_out_everywhere_and_reactivating_restores_access(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['email' => 'admin@exemplo.com']);
        $other = User::factory()->create();
        foreach ([[$admin->id, 'sessao-a'], [$admin->id, 'sessao-b'], [$other->id, 'sessao-c']] as [$userId, $id]) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }
        $token = $admin->remember_token;

        $this->actingAs($this->root)->patch(route('security.users.deactivate', $admin))->assertRedirect()->assertSessionHas('status');

        $this->assertFalse($admin->fresh()->ativo);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $admin->id)->count());
        $this->assertTrue(DB::table('sessions')->where('id', 'sessao-c')->exists());
        $this->assertNotSame($token, $admin->fresh()->remember_token);

        $this->patch(route('security.users.activate', $admin))->assertRedirect();
        $this->assertTrue($admin->fresh()->ativo);
    }

    public function test_a_session_opened_before_deactivation_is_rejected_on_the_next_request(): void
    {
        $admin = User::factory()->create(['email' => 'admin@exemplo.com', 'password' => 'senha-atual-123']);
        $this->post(route('login.store'), ['email' => 'admin@exemplo.com', 'password' => 'senha-atual-123']);
        $this->get(route('sales.index'))->assertOk();

        $admin->forceFill(['ativo' => false])->save();
        Auth::forgetGuards(); // a new request reloads the user instead of reusing the one cached by the guard

        $this->get(route('sales.index'))->assertRedirectToRoute('login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_root_resets_a_password_which_revokes_sessions_and_requires_a_change(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::factory()->create(['email' => 'admin@exemplo.com']);
        DB::table('sessions')->insert(['id' => 'sessao-a', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->root)->put(route('security.users.password', $admin), ['password' => 'temporaria-123', 'password_confirmation' => 'temporaria-123'])
            ->assertRedirect()->assertSessionHas('status');

        $admin->refresh();
        $this->assertTrue(Hash::check('temporaria-123', $admin->password));
        $this->assertTrue($admin->must_change_password);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $admin->id)->count());

        $this->put(route('security.users.password', $admin), ['password' => 'curta', 'password_confirmation' => 'curta'])
            ->assertSessionHasErrorsIn('resetPassword', ['password']);
    }

    public function test_a_session_opened_before_a_password_reset_is_rejected_whatever_the_session_driver(): void
    {
        $admin = User::factory()->create(['email' => 'admin@exemplo.com', 'password' => 'senha-atual-123']);
        $this->post(route('login.store'), ['email' => 'admin@exemplo.com', 'password' => 'senha-atual-123']);
        $this->get(route('sales.index'))->assertOk();

        $admin->forceFill(['password' => 'temporaria-123', 'must_change_password' => true])->save();
        Auth::forgetGuards(); // a new request reloads the user instead of reusing the one cached by the guard

        $this->get(route('sales.index'))->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_users_are_never_deleted_and_passwords_never_serialized(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($this->root)->delete('/seguranca/usuarios/'.$admin->id)->assertMethodNotAllowed();
        $this->assertModelExists($admin);
        $this->assertArrayNotHasKey('password', $admin->toArray());
        $this->assertArrayNotHasKey('remember_token', $admin->toArray());
    }
}
