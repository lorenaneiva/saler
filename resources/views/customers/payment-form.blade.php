<x-ui.errors />

<dl class="metric metric-emphasis">
    <dt>Saldo disponível para abater</dt>
    <dd><x-ui.money :value="$outstandingTotal" /></dd>
</dl>

@if ($outstandingTotal !== '0.00')
    @php
        $amount = old('amount');
        $paidOn = old('paid_on', now()->toDateString());
        $notes = old('notes');
    @endphp
    <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="grid gap-4">
        @csrf
        <input type="hidden" name="submission_token" value="{{ $paymentToken }}">
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="grid gap-1.5 text-sm font-medium">
                <span>Valor recebido (R$) <x-required-mark /></span>
                <input name="amount" type="number" min="0.01" max="{{ $outstandingTotal }}" step="0.01" class="field" required autofocus
                    value="{{ is_scalar($amount) ? $amount : '' }}" @error('amount') aria-invalid="true" @enderror>
            </label>
            <label class="grid gap-1.5 text-sm font-medium">
                <span>Data do pagamento <x-required-mark /></span>
                <input name="paid_on" type="date" max="{{ now()->toDateString() }}" class="field" required
                    value="{{ is_string($paidOn) ? $paidOn : '' }}" @error('paid_on') aria-invalid="true" @enderror>
            </label>
        </div>
        <label class="grid gap-1.5 text-sm font-medium">
            <span>Forma de pagamento <x-required-mark /></span>
            <select name="method" class="field" required @error('method') aria-invalid="true" @enderror>
                <option value="">Selecione</option>
                @foreach (\App\Models\Payment::Methods as $value => $label)
                    <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1.5 text-sm font-medium">
            <span>Observação (opcional)</span>
            <textarea name="notes" maxlength="2000" rows="2" class="field">{{ is_string($notes) ? $notes : '' }}</textarea>
        </label>
        <div class="grid gap-1 text-sm">
            <output class="font-semibold" data-payment-preview="{{ $outstandingTotal }}" aria-live="polite"></output>
            <p class="text-slate-600">O pagamento abate primeiro a compra mais antiga.</p>
        </div>
        <div class="page-actions">
            <button class="button-primary">Registrar pagamento</button>
            <button type="button" class="button-secondary" data-dialog-close>Cancelar</button>
        </div>
    </form>
@else
    <p class="text-sm text-slate-600">O cliente está sem saldo em aberto.</p>
@endif
