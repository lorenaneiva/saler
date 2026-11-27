<?php

namespace App\Http\Requests;

use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /** Keeps these errors apart from the password reset form on the same page. */
    protected $errorBag = 'createUser';

    public function authorize(): bool
    {
        return Gate::allows('manage-users');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'cargo' => ['required', 'string', Rule::in(Cargo::AssignableInInterface)],
            'situacao' => ['required', Rule::in(['ativo', 'inativo'])],
            'password' => PasswordRules::rules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.max' => 'O nome deve ter até 255 caracteres.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter até 255 caracteres.',
            'email.unique' => 'Já existe uma usuária com este e-mail.',
            'cargo.required' => 'Selecione o cargo.',
            'cargo.in' => 'Cargo inválido. Pela interface só é possível cadastrar administradores.',
            'situacao.*' => 'Selecione a situação.',
            ...PasswordRules::messages(),
        ];
    }
}
