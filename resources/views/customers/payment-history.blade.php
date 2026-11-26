<x-ui.data-table id="customer-payments" title="Histórico de pagamentos" :paginator="$payments" :unit="['pagamento', 'pagamentos']">
    <x-slot:filters>
        <x-ui.list-search label="Buscar pagamento" placeholder="Número, forma de pagamento ou observação" name="q_pagamentos" :keep="['secao', 'q_compras', 'page']" />
    </x-slot:filters>
    <thead>
        <tr>
            <th scope="col">Pagamento</th>
            <th scope="col">Data</th>
            <th scope="col">Forma de pagamento</th>
            <th scope="col" class="money">Valor</th>
            <th scope="col">Abatido em</th>
            <th scope="col">Observação</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($payments as $payment)
            <tr>
                <th scope="row">{{ $payment->id }}</th>
                <td class="whitespace-nowrap">{{ $payment->paid_on->format('d/m/Y') }}</td>
                <td>{{ \App\Models\Payment::Methods[$payment->method] }}</td>
                <td class="money font-semibold"><x-ui.money :value="$payment->amount" /></td>
                <td>
                    <ul class="grid gap-0.5">
                        @foreach ($payment->allocations as $allocation)
                            <li class="whitespace-nowrap">
                                <a href="{{ route('sales.show', $allocation->sale_id) }}" class="cell-link">Compra {{ $allocation->sale_id }}</a>
                                · <x-ui.money :value="$allocation->amount" />
                            </li>
                        @endforeach
                    </ul>
                </td>
                <td class="wrap-anywhere">{{ $payment->notes ?: '—' }}</td>
            </tr>
        @empty
            <x-ui.empty :columns="6">{{ request()->filled('q_pagamentos') ? 'Nenhum resultado para esta busca.' : 'Nenhum pagamento registrado para este cliente.' }}</x-ui.empty>
        @endforelse
    </tbody>
</x-ui.data-table>
