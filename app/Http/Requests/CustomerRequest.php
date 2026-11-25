<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'cpf' => ['nullable', 'string', 'regex:/^(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})$/D'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'postal_code' => ['nullable', 'string', 'regex:/^[0-9]{5}-?[0-9]{3}$/D'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'phone' => 'telefone',
            'cpf' => 'CPF',
            'email' => 'e-mail',
            'postal_code' => 'CEP',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Informe o campo :attribute.',
            'string' => 'O campo :attribute deve ser um texto.',
            'max.string' => 'O campo :attribute deve ter até :max caracteres.',
            'email' => 'Informe um e-mail válido.',
            'cpf.regex' => 'Informe o CPF com 11 dígitos, com ou sem pontuação.',
            'postal_code.regex' => 'Informe o CEP com 8 dígitos, com ou sem hífen.',
        ];
    }
}
