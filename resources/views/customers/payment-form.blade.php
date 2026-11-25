<section class="panel grid gap-4 p-5">
    <h2 class="text-xl font-semibold">Registrar pagamento</h2>
    @if ($errors->any())
        <ul role="alert" class="list-disc rounded-lg bg-red-50 py-3 pr-4 pl-9 text-sm text-red-900">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    @if ($outstandingTotal !== '0.00')
        @php
            $amount = old('amount');
            $paidOn = old('paid_on', now()->toDateString());
            $notes = old('notes');
        @endphp
        <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="submission_token" value="{{ $paymentToken }}">
            <label class="grid gap-2 font-medium">
                <span>Valor recebido (R$) <x-required-mark /></span>
                <input name="amount" type="number" min="0.01" step="0.01" class="field" required
                    value="{{ is_scalar($amount) ? $amount : '' }}">
            </label>
            <label class="grid gap-2 font-medium">
                <span>Data do pagamento <x-required-mark /></span>
                <input name="paid_on" type="date" max="{{ now()->toDateString() }}" class="field" required
                    value="{{ is_string($paidOn) ? $paidOn : '' }}">
            </label>
            <label class="grid content-start gap-2 font-medium">
                <span>Forma de pagamento <x-required-mark /></span>
                <select name="method" class="field" required>
                    <option value="">Selecione</option>
                    @foreach (\App\Models\Payment::Methods as $value => $label)
                        <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-2 font-medium">
                <span>Observação (opcional)</span>
                <textarea name="notes" maxlength="2000" rows="2" class="field">{{ is_string($notes) ? $notes : '' }}</textarea>
            </label>
            <p class="text-sm text-slate-600 sm:col-span-2">O valor é abatido das compras em aberto, começando pela mais antiga.</p>
            <button class="button-primary">Registrar pagamento</button>
        </form>
    @else
        <p class="text-sm text-slate-600">O cliente está sem saldo em aberto.</p>
    @endif
</section>
