@php
    $movementTypes = [
        \App\Models\StockMovement::ManualEntry => 'Entrada',
        \App\Models\StockMovement::ManualExit => 'Saída',
    ];
    $quantity = old('quantity');
    $justification = old('justification');
    $type = old('type', \App\Models\StockMovement::ManualEntry);
@endphp

<form method="POST" action="{{ route('stock.store', $product) }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <x-ui.errors class="sm:col-span-2" />

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
