<?php

namespace Tests\Feature;

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
}
