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
            'items.*' => ['required', 'array:product_id,new_product,quantity,unit_cost,discount'],
            'items.*.product_id' => [
                'required_without:items.*.new_product',
                'prohibits:items.*.new_product',
                'nullable',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.new_product' => ['sometimes', 'required', 'array:name,category_id,sale_price,barcode'],
            'items.*.new_product.name' => ['required_with:items.*.new_product', 'string', 'max:255'],
            'items.*.new_product.category_id' => ['required_with:items.*.new_product', 'integer', 'exists:categories,id'],
            'items.*.new_product.sale_price' => ['required_with:items.*.new_product', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'items.*.new_product.barcode' => ['nullable', 'string', 'max:64'],
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
            'items.*.product_id.prohibits' => 'Selecione um produto existente ou cadastre um novo para o item.',
            'items.*.product_id.*' => 'Selecione um produto ativo ou cadastre um novo.',
            'items.*.new_product.array' => 'Preencha os dados do novo produto.',
            'items.*.new_product.required' => 'Preencha os dados do novo produto.',
            'items.*.new_product.name.required_with' => 'Informe o nome do novo produto.',
            'items.*.new_product.name.string' => 'O nome do novo produto deve ser um texto.',
            'items.*.new_product.name.max' => 'O nome do novo produto deve ter até 255 caracteres.',
            'items.*.new_product.category_id.*' => 'Selecione uma categoria válida para o novo produto.',
            'items.*.new_product.sale_price.*' => 'Informe um preço de venda válido para o novo produto, com até duas casas decimais.',
            'items.*.new_product.barcode.*' => 'Informe um código de barras de até 64 caracteres.',
            'items.*.quantity.*' => 'Informe uma quantidade inteira entre 1 e 1.000.000.',
            'items.*.unit_cost.*' => 'Informe um custo unitário válido, com até duas casas decimais.',
            'items.*.discount.*' => 'Informe um desconto válido, com até duas casas decimais.',
        ];
    }
}
