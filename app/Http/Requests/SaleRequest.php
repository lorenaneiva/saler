<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sold_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_id.*' => 'Selecione um cliente cadastrado.',
            'sold_on.*' => 'Informe uma data de venda válida.',
            'items.required' => 'Adicione pelo menos um produto.',
            'items.max' => 'Adicione até 50 produtos.',
            'items.*.array' => 'Informe um item válido.',
            'items.*.product_id.distinct' => 'Adicione cada produto uma única vez.',
            'items.*.product_id.*' => 'Selecione um produto válido.',
            'items.*.quantity.*' => 'Informe uma quantidade inteira entre 1 e 1.000.000.',
            'discount.*' => 'Informe um desconto válido, com até duas casas decimais.',
            'notes.max' => 'A observação deve ter até 2.000 caracteres.',
            'notes.string' => 'A observação deve ser um texto.',
        ];
    }
}
