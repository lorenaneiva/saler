<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

/** Password policy shared by `app:create-user`, the Security module and the own-password change. */
final class PasswordRules
{
    public const MinimumLength = 10;

    /** @return list<mixed> */
    public static function rules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(self::MinimumLength), 'max:255'];
    }

    /** @return array<string, string> */
    public static function messages(string $field = 'password'): array
    {
        return [
            "{$field}.required" => 'Informe a senha.',
            "{$field}.min" => 'A senha deve ter pelo menos '.self::MinimumLength.' caracteres.',
            "{$field}.max" => 'A senha deve ter até 255 caracteres.',
            "{$field}.confirmed" => 'As senhas não conferem.',
        ];
    }
}
