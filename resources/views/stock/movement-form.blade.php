@php
    $movementTypes = [
        \App\Models\StockMovement::ManualEntry => 'Entrada',
        \App\Models\StockMovement::ManualExit => 'Saída',
    ];
    $quantity = old('quantity');
    $unitCost = old('unit_cost');
    $justification = old('justification');
    $type = old('type', \App\Models\StockMovement::ManualEntry);
    $isEntry = $type === \App\Models\StockMovement::ManualEntry;
@endphp

<form method="POST" action="{{ route('stock.store', $product) }}" class="panel grid gap-4 p-5 sm:grid-cols-2">
    @csrf
    <h2 class="text-xl font-semibold sm:col-span-2">Registrar movimentação</h2>

    @if ($errors->any())
        <ul role="alert" class="list-disc rounded-lg bg-red-50 py-3 pr-4 pl-9 text-sm text-red-900 sm:col-span-2">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    <label class="grid content-start gap-2 font-medium">
        <span>Tipo <x-required-mark /></span>
        <select id="movement-type" name="type" class="field" required>
            @foreach ($movementTypes as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid content-start gap-2 font-medium">
        <span>Quantidade <x-required-mark /></span>
        <input id="stock-quantity" name="quantity" type="number" class="field" min="1" max="2147483647" step="1"
            value="{{ is_scalar($quantity) ? $quantity : '' }}" required>
    </label>
    <label id="stock-unit-cost-field" class="grid content-start gap-2 font-medium" @if (! $isEntry) hidden @endif>
        <span>Custo unitário da entrada (R$) <x-required-mark /></span>
        <input id="stock-unit-cost" name="unit_cost" type="number" class="field" min="0" max="9999999999.9999" step="0.0001"
            value="{{ is_scalar($unitCost) ? $unitCost : '' }}" @required($isEntry) @disabled(! $isEntry)>
    </label>
    <div class="grid content-start gap-2">
        <label for="stock-reason" class="font-medium">Motivo <x-required-mark /></label>
        <select id="stock-reason" name="reason_id" class="field" required>
            <option value="">Selecione</option>
            @foreach ($reasons as $reason)
                <option value="{{ $reason->id }}" @selected(old('reason_id') == $reason->id)>{{ $reason->name }}</option>
            @endforeach
        </select>
        @if ($reasons->isEmpty())
            <p class="text-sm text-slate-600">Cadastre um motivo para registrar a movimentação.</p>
        @endif
        <a href="{{ route('stock.reasons.index') }}" class="w-fit text-sm text-emerald-800 underline">Motivos de ajuste</a>
    </div>
    <label class="grid content-start gap-2 font-medium">
        <span>Observação (opcional)</span>
        <textarea id="stock-justification" name="justification" class="field" rows="2" maxlength="500">{{ is_string($justification) ? $justification : '' }}</textarea>
    </label>

    <button class="button-primary sm:col-span-2">Registrar movimentação</button>
</form>

<script>
    const movementType = document.getElementById('movement-type');
    const unitCost = document.getElementById('stock-unit-cost');
    const unitCostField = document.getElementById('stock-unit-cost-field');

    movementType.addEventListener('change', () => {
        const isEntry = movementType.value === 'manual_entry';
        unitCostField.hidden = !isEntry;
        unitCost.disabled = !isEntry;
        unitCost.required = isEntry;
    });
</script>
