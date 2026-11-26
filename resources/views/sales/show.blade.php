@extends('layout')

@section('title', 'Detalhes da venda | Saler')

@section('content')
    <div class="grid gap-4">
        <x-ui.page-header :title="'Venda #'.$sale->id" :back="route('sales.index')" back-label="Vendas" />

        <div class="panel grid gap-4 p-5">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Cliente' => $sale->customer->name,
                    'Data da venda' => $sale->sold_on->format('d/m/Y'),
                ] as $label => $value)
                    <div>
                        <dt class="text-sm text-slate-600">{{ $label }}</dt>
                        <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                    </div>
                @endforeach
                @foreach (['Subtotal' => $sale->subtotal, 'Desconto' => $sale->discount, 'Total' => $sale->total] as $label => $amount)
                    <div>
                        <dt class="text-sm text-slate-600">{{ $label }}</dt>
                        <dd class="wrap-anywhere font-semibold"><x-ui.money :value="$amount" /></dd>
                    </div>
                @endforeach
            </dl>
            @if ($sale->notes)
                <p class="whitespace-pre-wrap wrap-anywhere text-sm text-slate-700">{{ $sale->notes }}</p>
            @endif
        </div>

        <dl class="metrics" aria-label="Resumo da venda">
            <div class="metric"><dt>Situação operacional</dt><dd>{{ $sale->status === 'cancelled' ? 'Cancelada' : 'Concluída' }}</dd></div>
            <div class="metric"><dt>Situação financeira</dt><dd>{{ $sale->paymentStatus() }}</dd></div>
            <div class="metric"><dt>Valor pago</dt><dd><x-ui.money :value="$sale->paidAmount()" /></dd></div>
            <div class="metric metric-emphasis"><dt>Saldo em aberto</dt><dd><x-ui.money :value="$sale->outstandingAmount()" /></dd></div>
        </dl>

        <x-ui.list-search label="Buscar item" placeholder="Nome ou código de barras" name="q_itens" :keep="['q_pagamentos', 'page']" />
        <x-ui.table label="Itens da venda">
                <thead>
                    <tr>
                        @foreach (['Produto', 'Quantidade', 'Preço unitário', 'Custo unitário da venda', 'Subtotal'] as $heading)
                            <th scope="col" @class(['numeric' => $loop->index >= 2])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sale->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 wrap-anywhere font-semibold">
                                <a data-row-link href="{{ route('stock.show', $item->product_id) }}">{{ $item->product->name }}</a>
                            </th>
                            <td>{{ $item->quantity }}</td>
                            @foreach ([$item->unit_price, $item->unit_cost, $item->subtotal] as $amount)
                                <td class="numeric"><x-ui.money :value="$amount" /></td>
                            @endforeach
                        </tr>
                    @empty
                        <x-ui.empty :columns="5">Nenhum item encontrado.</x-ui.empty>
                    @endforelse
                </tbody>
            </x-ui.table>

        <section class="grid gap-3">
            <h2 class="text-base font-semibold">Pagamentos desta venda</h2>
            <x-ui.list-search label="Buscar pagamento" placeholder="Número, forma de pagamento ou observação" name="q_pagamentos" :keep="['q_itens']" />
            <x-ui.table label="Pagamentos desta venda">
                <thead>
                    <tr>
                        <th scope="col">Pagamento</th>
                        <th scope="col">Data</th>
                        <th scope="col">Forma de pagamento</th>
                        <th scope="col" class="numeric">Valor abatido nesta venda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <th scope="row">{{ $payment->id }}</th>
                            <td>{{ $payment->paid_on->format('d/m/Y') }}</td>
                            <td>{{ \App\Models\Payment::Methods[$payment->method] }}</td>
                            <td class="numeric"><x-ui.money :value="$payment->allocation->amount" /></td>
                        </tr>
                    @empty
                        <x-ui.empty :columns="4">{{ request()->filled('q_pagamentos') ? 'Nenhum resultado para esta busca.' : 'Nenhum pagamento vinculado a esta venda.' }}</x-ui.empty>
                    @endforelse
                </tbody>
            </x-ui.table>
            <x-ui.pagination :paginator="$payments" />
        </section>
    </div>
@endsection
