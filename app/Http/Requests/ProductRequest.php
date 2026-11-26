<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ProductRequest extends FormRequest
{
    /** @return array<string, list<string|Exists>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', $this->selectableCategoryRule()],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'barcode' => ['nullable', 'string', 'max:64'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do produto.',
            'name.string' => 'O nome do produto deve ser um texto.',
            'name.max' => 'O nome deve ter até 255 caracteres.',
            'category_id.required' => 'Selecione uma categoria.',
            'category_id.integer' => 'Selecione uma categoria válida.',
            'category_id.exists' => 'Selecione uma categoria ativa.',
            'supplier_id.integer' => 'Selecione um fornecedor válido.',
            'supplier_id.exists' => 'O fornecedor selecionado não existe.',
            'sale_price.required' => 'Informe o preço de venda.',
            'sale_price.numeric' => 'Informe um preço de venda válido.',
            'sale_price.decimal' => 'Informe o preço com até duas casas decimais.',
            'sale_price.min' => 'O preço de venda deve ser maior ou igual a zero.',
            'sale_price.max' => 'O preço de venda excede o valor permitido.',
            'barcode.string' => 'O código de barras deve ser um texto.',
            'barcode.max' => 'O código de barras deve ter até 64 caracteres.',
        ];
    }

    /** Active categories are selectable; a product may keep its current category even after it is deactivated. */
    private function selectableCategoryRule(): Exists
    {
        $product = $this->route('product');
        $currentCategoryId = $product instanceof Product ? $product->category_id : null;

        return Rule::exists('categories', 'id')->where(fn (Builder $categories) => $categories
            ->where('is_active', true)
            ->when($currentCategoryId, fn (Builder $active) => $active->orWhere('id', $currentCategoryId)));
    }
}
