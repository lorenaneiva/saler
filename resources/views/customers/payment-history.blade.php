<section class="grid gap-3">
    <h2 class="text-base font-semibold">Histórico de pagamentos</h2>
    <ul class="panel divide-y divide-slate-200">
        @forelse ($payments as $payment)
            <li class="grid gap-2 px-5 py-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="font-semibold"><x-ui.money :value="$payment->amount" /></p>
                    <p class="text-sm text-slate-600">{{ $payment->paid_on->format('d/m/Y') }} · {{ \App\Models\Payment::Methods[$payment->method] }}</p>
                </div>
                @if ($payment->notes)
                    <p class="wrap-anywhere text-sm">{{ $payment->notes }}</p>
                @endif
                <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-700">
                    @foreach ($payment->allocations as $allocation)
                        <li>
                            <a href="{{ route('sales.show', $allocation->sale_id) }}" class="text-emerald-800 underline">Compra #{{ $allocation->sale_id }}</a>
                            · <x-ui.money :value="$allocation->amount" />
                        </li>
                    @endforeach
                </ul>
            </li>
        @empty
            <li class="px-5 py-4 text-sm text-slate-600">Nenhum pagamento registrado para este cliente.</li>
        @endforelse
    </ul>
    <x-ui.pagination :paginator="$payments" />
</section>
