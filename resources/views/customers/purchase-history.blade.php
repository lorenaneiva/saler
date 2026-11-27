<x-ui.data-table id="customer-sales" :title="$section === 'aberto' ? 'Contas em aberto' : 'Histórico de compras'" label="Compras do cliente" :paginator="$sales" :unit="['compra', 'compras']">
    <x-slot:filters>
        <x-ui.list-search label="Buscar compra" placeholder="Número da venda ou nome do produto" name="q_compras" :keep="['secao', 'q_pagamentos', 'pagamentos']" />
    </x-slot:filters>
            <thead>
                <tr>
                    <x-ui.sort-header :sort="$salesSort" column="numero">Compra</x-ui.sort-header>
                    <x-ui.sort-header :sort="$salesSort" column="data">Data</x-ui.sort-header>
                    <x-ui.sort-header :sort="$salesSort" column="total" class="money">Total</x-ui.sort-header>
                    <x-ui.sort-header :sort="$salesSort" column="pago" class="money">Pago</x-ui.sort-header>
                    <x-ui.sort-header :sort="$salesSort" column="aberto" class="money">Em aberto</x-ui.sort-header>
                    <x-ui.sort-header :sort="$salesSort" column="situacao">Situação</x-ui.sort-header>
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
