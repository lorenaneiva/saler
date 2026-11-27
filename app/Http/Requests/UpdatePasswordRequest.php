<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

/** Any signed-in user changes their own password after confirming the current one. */
class UpdatePasswordRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [...PasswordRules::rules(), function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && Hash::check($value, $this->user()->password)) {
                    $fail('A nova senha deve ser diferente da atual.');
                }
            }],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Informe a senha atual.',
            'current_password.current_password' => 'A senha atual não confere.',
            ...PasswordRules::messages(),
        ];
    }
}
