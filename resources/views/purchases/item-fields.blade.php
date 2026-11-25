@php
    $newProduct = $item['new_product'] ?? [];
    $newProduct = is_array($newProduct) ? $newProduct : [];
    $creatingProduct = $categories->isNotEmpty() && (array_key_exists('new_product', $item) || $products->isEmpty());
@endphp

<div data-item-row class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto] lg:items-end">
    <label class="grid gap-2 text-sm font-medium">
        Produto
        <select @if (! $creatingProduct) name="items[{{ $index }}][product_id]" @endif data-product-select class="field" required>
            <option value="">Selecione</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(! $creatingProduct && ($item['product_id'] ?? null) == $product->id)>{{ $product->name }}</option>
            @endforeach
            @if ($categories->isNotEmpty())
                <option value="new" @selected($creatingProduct)>Cadastrar novo produto</option>
            @endif
        </select>
    </label>
    @foreach ([
        'quantity' => ['Quantidade', '1', '1000000', '1', true],
        'unit_cost' => ['Custo unitário (R$)', '0', '999999.99', '0.01', true],
        'discount' => ['Desconto (R$)', '0', '999999999999.99', '0.01', false],
    ] as $field => [$label, $min, $max, $step, $required])
        @php($value = $item[$field] ?? null)
        <label class="grid gap-2 text-sm font-medium">
            {{ $label }}
            <input name="items[{{ $index }}][{{ $field }}]" data-item-field="{{ $field }}" type="number" class="field"
                min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" value="{{ is_scalar($value) ? $value : '' }}" @required($required)>
        </label>
    @endforeach
    <button type="button" data-remove-item class="button-secondary disabled:opacity-50">Remover</button>
    @if ($categories->isNotEmpty())
        <fieldset data-new-product-fields @if (! $creatingProduct) hidden disabled @endif
            class="rounded-lg border border-slate-200 bg-slate-50 p-3 sm:col-span-2 lg:col-span-full">
            <legend class="px-1 text-sm font-semibold">Novo produto</legend>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'name' => ['Nome', 'text', 255, true],
                    'barcode' => ['Código de barras (opcional)', 'text', 64, false],
                    'sale_price' => ['Preço de venda (R$)', 'number', null, true],
                ] as $field => [$label, $type, $maxLength, $required])
                    @php($value = $newProduct[$field] ?? null)
                    <label class="grid gap-2 text-sm font-medium">
                        {{ $label }}
                        <input name="items[{{ $index }}][new_product][{{ $field }}]" data-new-product-field="{{ $field }}"
                            type="{{ $type }}" class="field" value="{{ is_scalar($value) ? $value : '' }}" @required($required)
                            @if ($maxLength) maxlength="{{ $maxLength }}" @endif
                            @if ($field === 'sale_price') min="0" max="9999999999.99" step="0.01" @endif>
                    </label>
                @endforeach
                <label class="grid gap-2 text-sm font-medium">
                    Categoria
                    <select name="items[{{ $index }}][new_product][category_id]" data-new-product-field="category_id" class="field" required>
                        <option value="">Selecione</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($newProduct['category_id'] ?? null) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </fieldset>
    @endif
</div>
