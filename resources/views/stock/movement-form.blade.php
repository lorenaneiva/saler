@php
    $movementTypes = [
        \App\Models\StockMovement::ManualEntry => 'Entrada manual',
        \App\Models\StockMovement::ManualExit => 'Saída manual',
        \App\Models\StockMovement::Adjustment => 'Ajuste manual',
    ];
    $quantity = old('quantity');
    $unitCost = old('unit_cost');
    $justification = old('justification');
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
                <option value="{{ $value }}" @selected(old('type', \App\Models\StockMovement::ManualEntry) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid content-start gap-2 font-medium">
        <span><span id="stock-quantity-label">Quantidade</span> <x-required-mark /></span>
        <input id="stock-quantity" name="quantity" type="number" class="field" min="0" max="2147483647" step="1"
            value="{{ is_scalar($quantity) ? $quantity : '' }}" required>
        <span id="stock-adjustment-help" class="text-sm font-normal text-slate-600" hidden>Informe o saldo contado, incluindo zero.</span>
    </label>
    <label id="stock-unit-cost-field" class="grid content-start gap-2 font-medium">
        <span>Custo unitário da entrada (R$) <x-required-mark /></span>
        <input id="stock-unit-cost" name="unit_cost" type="number" class="field" min="0" max="9999999999.9999" step="0.0001"
            value="{{ is_scalar($unitCost) ? $unitCost : '' }}">
    </label>
    <label class="grid content-start gap-2 font-medium">
        <span>Justificativa <span id="stock-justification-required" hidden><x-required-mark /></span></span>
        <textarea id="stock-justification" name="justification" class="field" rows="2" maxlength="500">{{ is_string($justification) ? $justification : '' }}</textarea>
    </label>

    <button class="button-primary sm:col-span-2">Registrar movimentação</button>
</form>

<script>
    const movementType = document.getElementById('movement-type');
    const quantity = document.getElementById('stock-quantity');
    const quantityLabel = document.getElementById('stock-quantity-label');
    const adjustmentHelp = document.getElementById('stock-adjustment-help');
    const unitCost = document.getElementById('stock-unit-cost');
    const unitCostField = document.getElementById('stock-unit-cost-field');
    const justification = document.getElementById('stock-justification');
    const justificationRequired = document.getElementById('stock-justification-required');

    function updateMovementFields() {
        const isEntry = movementType.value === 'manual_entry';
        const isAdjustment = movementType.value === 'adjustment';
        quantityLabel.textContent = isAdjustment ? 'Saldo final' : 'Quantidade';
        quantity.min = isAdjustment ? '0' : '1';
        adjustmentHelp.hidden = !isAdjustment;
        unitCostField.hidden = !isEntry;
        unitCost.disabled = !isEntry;
        unitCost.required = isEntry;
        justificationRequired.hidden = !isAdjustment;
        justification.required = isAdjustment;
    }

    movementType.addEventListener('change', updateMovementFields);
    updateMovementFields();
</script>
