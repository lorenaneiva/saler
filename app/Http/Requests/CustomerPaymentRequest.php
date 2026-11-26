<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerPaymentRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999999999.99'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:today'],
            'method' => ['required', Rule::in(array_keys(Payment::Methods))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'submission_token.*' => 'Recarregue a página antes de registrar o pagamento.',
            'amount.*' => 'Informe um valor de pagamento maior que zero, com até duas casas decimais.',
            'paid_on.*' => 'Informe uma data de pagamento válida, até hoje.',
            'method.*' => 'Selecione uma forma de pagamento.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
            'notes.string' => 'A observação deve ser um texto.',
        ];
    }
}
