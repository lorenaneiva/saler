<?php

namespace App\Actions;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterUser
{
    /**
     * Creates a new user; never updates an existing one. A Root is only created while no other Root exists: the
     * check runs under a lock on the Root role and the partial unique index `users_single_root_unique` rejects
     * any concurrent creation that slips through.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data, string $cargoSlug, bool $mustChangePassword = false, bool $ativo = true): User
    {
        try {
            return DB::transaction(function () use ($data, $cargoSlug, $mustChangePassword, $ativo): User {
                $cargo = Cargo::query()->where('slug', $cargoSlug)->lockForUpdate()->first()
                    ?? throw ValidationException::withMessages(['cargo' => 'Cargo inválido.']);

                if ($cargo->slug === Cargo::Root && User::query()->where('cargo_id', $cargo->id)->exists()) {
                    throw ValidationException::withMessages(['cargo' => 'Já existe uma conta Root. O sistema permite apenas uma.']);
                }

                $user = new User(['name' => $data['name'], 'email' => mb_strtolower(trim($data['email'])), 'password' => $data['password']]);
                $user->forceFill(['cargo_id' => $cargo->id, 'ativo' => $ativo, 'must_change_password' => $mustChangePassword])->save();

                return $user;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23505') {
                throw $exception;
            }

            $root = str_contains($exception->getMessage(), 'users_single_root_unique');

            throw ValidationException::withMessages($root
                ? ['cargo' => 'Já existe uma conta Root. O sistema permite apenas uma.']
                : ['email' => 'Já existe uma usuária com este e-mail.']);
        }
    }
}
