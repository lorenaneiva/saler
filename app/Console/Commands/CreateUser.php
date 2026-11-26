<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates a user who can sign in. The password is always typed interactively (hidden), never passed
 * as an argument or environment variable, so it does not end up in shell history or service settings.
 */
#[Signature('app:create-user {--name= : Nome da usuária} {--email= : E-mail usado no login}')]
#[Description('Cadastra uma usuária com acesso ao sistema')]
class CreateUser extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nome', required: true, validate: ['name' => ['max:255']]);
        $email = $this->option('email') ?: text('E-mail', required: true, validate: ['email' => ['email', 'max:255']]);
        $password = password('Senha (mínimo de 10 caracteres)', required: true);
        $confirmation = password('Confirme a senha', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => mb_strtolower(trim($email)), 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'password' => ['required', 'confirmed', Password::min(10)],
            ],
            [
                'email.unique' => 'Já existe uma usuária com este e-mail.',
                'password.confirmed' => 'As senhas não conferem.',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->create($validator->safe()->only(['name', 'email', 'password']));

        $this->info("Usuária {$user->email} cadastrada. Ela já pode entrar no sistema.");

        return self::SUCCESS;
    }
}
