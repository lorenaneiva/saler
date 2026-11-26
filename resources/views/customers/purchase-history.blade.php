<x-ui.data-table id="customer-sales" :title="$section === 'aberto' ? 'Contas em aberto' : 'Histórico de compras'" label="Compras do cliente" :paginator="$sales" :unit="['compra', 'compras']">
    <x-slot:filters>
        <x-ui.list-search label="Buscar compra" placeholder="Número da venda ou nome do produto" name="q_compras" :keep="['secao', 'q_pagamentos', 'pagamentos']" />
    </x-slot:filters>
            <thead>
                <tr>
                    @foreach (['Compra', 'Data', 'Total', 'Pago', 'Em aberto', 'Situação'] as $heading)
                        <th scope="col" @class(['money' => in_array($heading, ['Total', 'Pago', 'Em aberto'], true)])>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <th scope="row" >
                            <a data-row-link href="{{ route('sales.show', $sale) }}">{{ $sale->id }}</a>
                        </th>
                        <td class="whitespace-nowrap">{{ $sale->sold_on->format('d/m/Y') }}</td>
                        <td class="money"><x-ui.money :value="$sale->total" /></td>
                        <td class="money"><x-ui.money :value="$sale->paidAmount()" /></td>
                        <td class="money"><x-ui.money :value="$sale->outstandingAmount()" /></td>
                        <td><x-ui.payment-status :status="$sale->paymentStatus()" /></td>
                    </tr>
                @empty
                    <x-ui.empty :columns="6">{{ request()->filled('q_compras') ? 'Nenhum resultado para esta busca.' : ($section === 'aberto' ? 'Nenhuma conta em aberto.' : 'Nenhuma compra registrada para este cliente.') }}</x-ui.empty>
                @endforelse
            </tbody>
</x-ui.data-table>
