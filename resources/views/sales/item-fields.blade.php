@php($quantity = $item['quantity'] ?? 1)

<div data-item-row class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_auto] sm:items-end">
    <label class="grid gap-2 text-sm font-medium">
        <span>Produto <x-required-mark /></span>
        <select name="items[{{ $index }}][product_id]" data-item-field="product_id" class="field" required>
            <option value="">Selecione</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(($item['product_id'] ?? null) == $product->id)>
                    {{ $product->name }} · R$ {{ str_replace('.', ',', $product->sale_price) }} · {{ $product->stock_quantity }} em estoque
                </option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium">
        <span>Quantidade <x-required-mark /></span>
        <input name="items[{{ $index }}][quantity]" data-item-field="quantity" class="field" type="number"
            min="1" max="1000000" step="1" required value="{{ is_scalar($quantity) ? $quantity : '' }}">
    </label>
    <button type="button" data-remove-item class="button-secondary disabled:opacity-50">Remover</button>
</div>
