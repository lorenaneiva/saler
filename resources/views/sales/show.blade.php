@extends('layout')

@section('title', 'Detalhes da venda | Saler')

@section('content')
    @php
        $isCompleted = $sale->status === \App\Models\Sale::Completed;
        $paymentStatus = $sale->paymentStatus();
        $outstandingAmount = $sale->outstandingAmount();
        $hasOutstandingAmount = $outstandingAmount->isPositive();
        $hasDiscount = \Brick\Math\BigDecimal::of($sale->discount ?? 0)->isPositive();
        $visibleItems = $sale->items->count();
        $itemsMeta = request()->filled('q_itens')
            ? $visibleItems.' de '.$sale->items_count.' '.($sale->items_count === 1 ? 'item' : 'itens')
            : $sale->items_count.' '.($sale->items_count === 1 ? 'item' : 'itens');
        $paymentsMeta = request()->filled('q_pagamentos')
            ? $payments->total().' de '.$sale->payments_count.' '.($sale->payments_count === 1 ? 'pagamento' : 'pagamentos')
            : $sale->payments_count.' '.($sale->payments_count === 1 ? 'pagamento' : 'pagamentos');
    @endphp

    <div class="grid gap-4">
        <x-ui.page-header :title="'Venda #'.$sale->id" :back="route('sales.index')" back-label="Vendas" />

        <section class="panel sale-summary" aria-label="Resumo da venda">
            <div class="sale-summary-main">
                <dl class="sale-facts">
                    <div>
                        <dt>Cliente</dt>
                        <dd class="wrap-anywhere">{{ $sale->customer->name }}</dd>
                    </div>
                    <div>
                        <dt>Data da venda</dt>
                        <dd>{{ $sale->sold_on->format('d/m/Y') }}</dd>
                    </div>
                </dl>
                <dl class="sale-total">
                    <dt>Total da venda</dt>
                    <dd><x-ui.money :value="$sale->total" /></dd>
                </dl>
            </div>

            <dl class="sale-status">
                <div>
                    <dt>Situação operacional</dt>
                    <dd><x-ui.badge :tone="$isCompleted ? 'success' : 'neutral'">{{ $isCompleted ? 'Concluída' : 'Cancelada' }}</x-ui.badge></dd>
                </div>
                <div>
                    <dt>Situação financeira</dt>
                    <dd>
                        @if ($isCompleted)
                            <x-ui.badge :tone="match ($paymentStatus) { 'Quitada' => 'success', 'Pendente' => 'danger', default => 'warning' }">{{ $paymentStatus }}</x-ui.badge>
                        @else
                            {{ $paymentStatus }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Valor pago</dt>
                    <dd class="tabular-nums"><x-ui.money :value="$sale->paidAmount()" /></dd>
                </div>
                <div @class(['sale-outstanding' => $hasOutstandingAmount])>
                    <dt>Saldo em aberto</dt>
                    <dd class="tabular-nums"><x-ui.money :value="$outstandingAmount" /></dd>
                </div>
            </dl>

            @if ($sale->notes)
                <p class="sale-notes">{{ $sale->notes }}</p>
            @endif
        </section>

        <x-ui.table-section id="sale-items" title="Produtos da venda" :meta="$itemsMeta">
            <x-slot:search>
                <x-ui.list-search embedded label="Buscar produto da venda" placeholder="Nome ou código de barras" name="q_itens" :keep="['q_pagamentos', 'page']" />
            </x-slot:search>

            <div id="sale-items-results" class="table-results" data-live-region>
                <x-ui.table label="Produtos da venda" :framed="false">
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
                            <x-ui.empty :columns="5">{{ request()->filled('q_itens') ? 'Nenhum produto desta venda corresponde à busca.' : 'Nenhum item registrado nesta venda.' }}</x-ui.empty>
                        @endforelse
                    </tbody>
                    <tfoot class="table-totals">
                        <tr>
                            <th scope="row" colspan="4">Subtotal</th>
                            <td class="numeric"><x-ui.money :value="$sale->subtotal" /></td>
                        </tr>
                        <tr>
                            <th scope="row" colspan="4">Desconto</th>
                            <td class="numeric">{{ $hasDiscount ? '− ' : '' }}<x-ui.money :value="$sale->discount" /></td>
                        </tr>
                        <tr class="table-grand-total">
                            <th scope="row" colspan="4">Total da venda</th>
                            <td class="numeric"><x-ui.money :value="$sale->total" /></td>
                        </tr>
                    </tfoot>
                </x-ui.table>
            </div>
        </x-ui.table-section>

        <x-ui.table-section id="sale-payments" title="Pagamentos desta venda" :meta="$paymentsMeta">
            <x-slot:search>
                <x-ui.list-search embedded label="Buscar pagamento" placeholder="Número, forma ou observação" name="q_pagamentos" :keep="['q_itens']" :disabled="$sale->payments_count === 0" />
            </x-slot:search>

            <div id="sale-payments-results" class="table-results" data-live-region>
                <x-ui.table label="Pagamentos desta venda" :framed="false">
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
                            <tr>
                                <td colspan="4" class="empty-state">
                                    @if ($sale->payments_count === 0)
                                        <p class="font-semibold text-slate-700">Nenhum pagamento vinculado a esta venda.</p>
                                        <p class="mt-1">
                                            @if ($hasOutstandingAmount)
                                                Quando um pagamento do cliente for abatido nesta venda, ele aparecerá aqui.
                                            @else
                                                Não há valores a receber nesta venda.
                                            @endif
                                        </p>
                                        @if ($hasOutstandingAmount)
                                            <a class="button-quiet mt-2" href="{{ route('customers.show', $sale->customer_id) }}">Abrir cliente para registrar pagamento</a>
                                        @endif
                                    @else
                                        Nenhum pagamento corresponde à busca.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
                <x-ui.pagination :paginator="$payments" />
            </div>
        </x-ui.table-section>
    </div>
@endsection
