<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_a_user_with_a_hashed_password_typed_interactively(): void
    {
        $this->artisan('app:create-user', ['--name' => 'Lorena', '--email' => 'Lorena@Exemplo.com'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'senha-forte-123')
            ->expectsOutputToContain('Usuária lorena@exemplo.com cadastrada.')
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertSame('lorena@exemplo.com', $user->email);
        $this->assertNotSame('senha-forte-123', $user->password);
        $this->assertTrue(Hash::check('senha-forte-123', $user->password));

        $this->post(route('login.store'), ['email' => 'lorena@exemplo.com', 'password' => 'senha-forte-123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_duplicate_email_short_password_and_mismatched_confirmation_without_creating_users(): void
    {
        User::factory()->create(['email' => 'lorena@exemplo.com']);

        $this->artisan('app:create-user', ['--name' => 'Outra', '--email' => 'lorena@exemplo.com'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'senha-forte-123')
            ->expectsOutputToContain('Já existe uma usuária com este e-mail.')
            ->assertFailed();

        $this->artisan('app:create-user', ['--name' => 'Nova', '--email' => 'nova@exemplo.com'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'curta')
            ->expectsQuestion('Confirme a senha', 'diferente')
            ->expectsOutputToContain('As senhas não conferem.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_role_defaults_to_administrador(): void
    {
        $this->artisan('app:create-user', ['--name' => 'Ana', '--email' => 'ana@exemplo.com'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'senha-forte-123')
            ->expectsOutputToContain('Cargo: Administrador.')
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertFalse($user->isRoot());
        $this->assertFalse($user->must_change_password);
    }

    public function test_creates_the_first_root_with_the_role_option(): void
    {
        User::factory()->create(['email' => 'admin@exemplo.com']);

        $this->artisan('app:create-user', ['--name' => 'Dona', '--email' => 'root@exemplo.com', '--role' => 'ROOT'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'senha-forte-123')
            ->expectsOutputToContain('Usuária root@exemplo.com cadastrada. Cargo: Root.')
            ->assertSuccessful();

        $root = User::query()->where('email', 'root@exemplo.com')->sole();
        $this->assertTrue($root->isRoot());
        $this->assertTrue($root->ativo);
        $this->assertTrue(Hash::check('senha-forte-123', $root->password));
        $this->assertFalse(User::query()->where('email', 'admin@exemplo.com')->sole()->isRoot());

        $this->post(route('login.store'), ['email' => 'root@exemplo.com', 'password' => 'senha-forte-123'])->assertRedirect(route('dashboard'));
        $this->get(route('security.users.index'))->assertOk();
    }

    public function test_refuses_a_second_root_before_asking_for_a_password(): void
    {
        User::factory()->root()->create();

        $this->artisan('app:create-user', ['--name' => 'Outra', '--email' => 'outra@exemplo.com', '--role' => 'root'])
            ->expectsOutputToContain('Já existe uma conta Root. O sistema permite apenas uma.')
            ->assertFailed();

        $this->assertSame(1, User::query()->where('cargo_id', Cargo::idFor(Cargo::Root))->count());
        $this->assertDatabaseMissing('users', ['email' => 'outra@exemplo.com']);
    }

    public function test_rejects_an_unknown_role(): void
    {
        $this->artisan('app:create-user', ['--name' => 'Outra', '--email' => 'outra@exemplo.com', '--role' => 'superadmin'])
            ->expectsOutputToContain('Cargo inválido. Use: root, administrador.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_never_overwrites_an_existing_user_when_creating_root(): void
    {
        $existing = User::factory()->create(['email' => 'lorena@exemplo.com', 'name' => 'Lorena']);
        $password = $existing->password;

        $this->artisan('app:create-user', ['--name' => 'Root', '--email' => 'LORENA@exemplo.com', '--role' => 'root'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'senha-forte-123')
            ->expectsOutputToContain('Já existe uma usuária com este e-mail.')
            ->assertFailed();

        $existing->refresh();
        $this->assertSame(['Lorena', $password, false], [$existing->name, $existing->password, $existing->isRoot()]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_short_passwords_are_rejected_with_the_shared_policy(): void
    {
        $this->artisan('app:create-user', ['--name' => 'Ana', '--email' => 'ana@exemplo.com', '--role' => 'root'])
            ->expectsQuestion('Senha (mínimo de 10 caracteres)', 'curta')
            ->expectsQuestion('Confirme a senha', 'curta')
            ->expectsOutputToContain('A senha deve ter pelo menos 10 caracteres.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
