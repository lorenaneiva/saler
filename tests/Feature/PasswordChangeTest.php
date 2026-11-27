<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_any_user_changes_their_own_password_after_confirming_the_current_one(): void
    {
        foreach ([User::factory()->root()->create(['password' => 'senha-atual-123']), User::factory()->create(['password' => 'senha-atual-123'])] as $user) {
            $this->flushSession();
            $this->actingAs($user)->get(route('password.edit'))->assertOk()->assertSeeText('Alterar senha');

            $this->put(route('password.update'), ['current_password' => 'senha-atual-123', 'password' => 'senha-nova-456', 'password_confirmation' => 'senha-nova-456'])
                ->assertRedirectToRoute('dashboard')->assertSessionHas('status', 'Senha alterada.');

            $this->assertTrue(Hash::check('senha-nova-456', $user->fresh()->password));
            $this->get(route('dashboard'))->assertOk();
        }
    }

    public function test_wrong_current_password_reused_password_and_short_password_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'senha-atual-123']);
        $this->actingAs($user)->from(route('password.edit'));

        $this->put(route('password.update'), ['current_password' => 'errada-12345', 'password' => 'senha-nova-456', 'password_confirmation' => 'senha-nova-456'])
            ->assertSessionHasErrors(['current_password' => 'A senha atual não confere.']);
        $this->put(route('password.update'), ['current_password' => 'senha-atual-123', 'password' => 'senha-atual-123', 'password_confirmation' => 'senha-atual-123'])
            ->assertSessionHasErrors(['password' => 'A nova senha deve ser diferente da atual.']);
        $this->put(route('password.update'), ['current_password' => 'senha-atual-123', 'password' => 'curta', 'password_confirmation' => 'curta'])
            ->assertSessionHasErrors(['password' => 'A senha deve ter pelo menos 10 caracteres.']);

        $this->assertTrue(Hash::check('senha-atual-123', $user->fresh()->password));
    }

    public function test_a_temporary_password_must_be_replaced_before_using_the_system(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => 'temporaria-123']);
        $this->actingAs($user);

        foreach ([route('dashboard'), route('sales.index'), route('products.index')] as $url) {
            $this->get($url)->assertRedirectToRoute('password.edit');
        }
        $this->getJson(route('autocomplete.options', 'customers'))->assertForbidden();
        $this->get(route('password.edit'))->assertOk()->assertSeeText('Sua senha foi definida por outra pessoa');

        $this->put(route('password.update'), ['current_password' => 'temporaria-123', 'password' => 'minha-senha-789', 'password_confirmation' => 'minha-senha-789'])
            ->assertRedirectToRoute('dashboard');

        $this->assertFalse($user->fresh()->must_change_password);
        $this->get(route('sales.index'))->assertOk();
    }

    public function test_changing_the_password_signs_out_other_sessions_but_keeps_the_current_one(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'senha-atual-123']);
        DB::table('sessions')->insert(['id' => 'outro-aparelho', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->put(route('password.update'), ['current_password' => 'senha-atual-123', 'password' => 'senha-nova-456', 'password_confirmation' => 'senha-nova-456'])
            ->assertRedirectToRoute('dashboard');

        $this->assertFalse(DB::table('sessions')->where('id', 'outro-aparelho')->exists());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_inactive_users_cannot_sign_in_and_wrong_passwords_get_the_generic_message(): void
    {
        User::factory()->inactive()->create(['email' => 'inativa@exemplo.com', 'password' => 'senha-certa-123']);

        $this->post(route('login.store'), ['email' => 'inativa@exemplo.com', 'password' => 'senha-errada-1'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
        $this->post(route('login.store'), ['email' => 'inativa@exemplo.com', 'password' => 'senha-certa-123'])
            ->assertSessionHasErrors(['email' => 'Sua conta está desativada. Procure a pessoa responsável pelo sistema.']);

        $this->assertGuest();
    }

    public function test_successful_login_records_the_last_access_and_failed_login_does_not(): void
    {
        $user = User::factory()->create(['email' => 'ana@exemplo.com', 'password' => 'senha-certa-123']);

        $this->post(route('login.store'), ['email' => 'ana@exemplo.com', 'password' => 'errada-12345']);
        $this->assertNull($user->fresh()->last_login_at);

        $this->travelTo('2026-11-27 10:15:00');
        $this->post(route('login.store'), ['email' => 'ana@exemplo.com', 'password' => 'senha-certa-123'])->assertRedirect(route('dashboard'));

        $this->assertSame('2026-11-27 10:15:00', $user->fresh()->last_login_at->format('Y-m-d H:i:s'));
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/registrar')->assertNotFound();
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@exemplo.com', 'password' => 'senha-forte-123'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }
}
