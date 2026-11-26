<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $creating = ! $this->route('product') instanceof Product;
        $withInitialPurchase = $creating && $this->boolean('initial_purchase');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', $this->selectableCategoryRule()],
            'supplier_id' => $withInitialPurchase
                ? ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)]
                : ['nullable', 'integer', 'exists:suppliers,id'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'submission_token' => [$creating ? 'nullable' : 'prohibited', 'uuid'],
            'initial_purchase' => [$creating ? 'nullable' : 'prohibited', 'boolean'],
            ...($withInitialPurchase ? [
                'purchased_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
                ...PurchaseRequest::itemCostRules(),
            ] : []),
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->boolean('initial_purchase') && PurchaseRequest::discountExceedsSubtotal($validator->validated())) {
                    $validator->errors()->add('discount', 'O desconto não pode ultrapassar o valor bruto da compra (quantidade × custo unitário).');
                }
            },
        ];
    }

    /**
     * The validated product attributes, without the initial purchase fields.
     *
     * @return array{name: string, category_id: int, supplier_id: ?int, sale_price: string, barcode: ?string, submission_token?: ?string}
     */
    public function productData(): array
    {
        return Arr::only($this->validated(), ['name', 'category_id', 'supplier_id', 'sale_price', 'barcode', 'submission_token']);
    }

    /**
     * The validated initial purchase, when the user chose to register one.
     *
     * @return ?array{purchased_on: string, quantity: int, unit_cost: string, discount: ?string}
     */
    public function initialPurchase(): ?array
    {
        return $this->boolean('initial_purchase') ? Arr::only($this->validated(), ['purchased_on', 'quantity', 'unit_cost', 'discount']) : null;
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
            'supplier_id.required' => 'Selecione o fornecedor da compra inicial.',
            'supplier_id.integer' => 'Selecione um fornecedor válido.',
            'supplier_id.exists' => $this->boolean('initial_purchase') ? 'Selecione um fornecedor ativo para a compra inicial.' : 'O fornecedor selecionado não existe.',
            'sale_price.required' => 'Informe o preço de venda.',
            'sale_price.numeric' => 'Informe um preço de venda válido.',
            'sale_price.decimal' => 'Informe o preço com até duas casas decimais.',
            'sale_price.min' => 'O preço de venda deve ser maior ou igual a zero.',
            'sale_price.max' => 'O preço de venda excede o valor permitido.',
            'barcode.string' => 'O código de barras deve ser um texto.',
            'barcode.max' => 'O código de barras deve ter até 64 caracteres.',
            'submission_token.*' => 'Recarregue a página antes de cadastrar o produto.',
            'initial_purchase.*' => 'A compra inicial só pode ser registrada no cadastro de um novo produto.',
            'purchased_on.*' => 'Informe uma data de compra válida.',
            'quantity.*' => 'Informe uma quantidade inteira entre 1 e 1.000.000.',
            'unit_cost.*' => 'Informe um custo unitário válido, com até duas casas decimais.',
            'discount.*' => 'Informe um desconto válido, com até duas casas decimais.',
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
