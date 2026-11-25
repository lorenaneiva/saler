<?php

namespace App\Http\Requests;

use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'purchased_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:product_id,quantity,unit_cost,discount'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach ($validator->validated()['items'] as $index => $item) {
                    $subtotal = BigDecimal::of($item['unit_cost'])->multipliedBy($item['quantity']);

                    if ($subtotal->isLessThan($item['discount'] ?? 0)) {
                        $validator->errors()->add("items.{$index}.discount", 'O desconto não pode ultrapassar o subtotal do item.');
                    }
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'supplier_id.*' => 'Selecione um fornecedor ativo.',
            'purchased_on.*' => 'Informe uma data de compra válida.',
            'items.required' => 'Adicione pelo menos um item.',
            'items.max' => 'Adicione até 50 itens.',
            'items.*.array' => 'Informe um item válido.',
            'items.*.product_id.distinct' => 'Adicione cada produto uma única vez.',
            'items.*.product_id.*' => 'Selecione um produto ativo.',
            'items.*.quantity.*' => 'Informe uma quantidade inteira entre 1 e 1.000.000.',
            'items.*.unit_cost.*' => 'Informe um custo unitário válido, com até duas casas decimais.',
            'items.*.discount.*' => 'Informe um desconto válido, com até duas casas decimais.',
        ];
    }
}
