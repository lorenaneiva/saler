<div data-item-row class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto] lg:items-end">
    <label class="grid gap-2 text-sm font-medium">
        Produto
        <select name="items[{{ $index }}][product_id]" data-item-field="product_id" class="field" required>
            <option value="">Selecione</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(($item['product_id'] ?? null) == $product->id)>{{ $product->name }}</option>
            @endforeach
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
</div>
