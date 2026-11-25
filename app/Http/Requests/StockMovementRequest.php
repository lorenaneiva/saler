<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([StockMovement::ManualEntry, StockMovement::ManualExit])],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason_id' => ['required', 'integer', 'exists:motivos,id'],
            'justification' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.*' => 'Selecione um tipo de movimentação válido.',
            'quantity.*' => 'Informe uma quantidade inteira maior que zero.',
            'reason_id.*' => 'Selecione um motivo cadastrado para a movimentação.',
            'justification.string' => 'A justificativa deve ser um texto.',
            'justification.max' => 'A justificativa deve ter até 500 caracteres.',
        ];
    }
}
