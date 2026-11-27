<?php

namespace App\Console\Commands;

use App\Actions\RegisterUser;
use App\Http\Requests\PasswordRules;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates a user who can sign in. The password is always typed interactively (hidden), never passed
 * as an argument or environment variable, so it does not end up in shell history or service settings.
 * It is the only way to create the Root account, and it refuses a second Root.
 */
#[Signature('app:create-user {--name= : Nome da usuária} {--email= : E-mail usado no login} {--role=administrador : Cargo (slug): root ou administrador}')]
#[Description('Cadastra uma usuária com acesso ao sistema')]
class CreateUser extends Command
{
    public function handle(RegisterUser $registerUser): int
    {
        $role = mb_strtolower(trim((string) $this->option('role')));
        $cargo = Cargo::query()->where('slug', $role)->first();
        if ($cargo === null) {
            $this->error('Cargo inválido. Use: '.Cargo::query()->orderBy('id')->pluck('slug')->implode(', ').'.');

            return self::FAILURE;
        }

        if ($cargo->slug === Cargo::Root && $cargo->users()->exists()) {
            $this->error('Já existe uma conta Root. O sistema permite apenas uma.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: text('Nome', required: true, validate: ['name' => ['max:255']]);
        $email = $this->option('email') ?: text('E-mail', required: true, validate: ['email' => ['email', 'max:255']]);
        $password = password('Senha (mínimo de '.PasswordRules::MinimumLength.' caracteres)', required: true);
        $confirmation = password('Confirme a senha', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => mb_strtolower(trim($email)), 'password' => $password, 'password_confirmation' => $confirmation],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'password' => PasswordRules::rules(),
            ],
            [
                'email.unique' => 'Já existe uma usuária com este e-mail.',
                ...PasswordRules::messages(),
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            $user = $registerUser->handle($validator->safe()->only(['name', 'email', 'password']), $cargo->slug);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                $this->error(implode(' ', $messages));
            }

            return self::FAILURE;
        }

        $this->info("Usuária {$user->email} cadastrada. Cargo: {$cargo->name}. Ela já pode entrar no sistema.");

        return self::SUCCESS;
    }
}
