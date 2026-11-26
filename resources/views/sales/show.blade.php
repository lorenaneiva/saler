@extends('layout')

@section('title', 'Detalhes da venda | Saler')

@section('content')
    @php
        $isCompleted = $sale->status === \App\Models\Sale::Completed;
        $paymentStatus = $sale->paymentStatus();
        $outstandingAmount = $sale->outstandingAmount();
        $hasOutstandingAmount = $outstandingAmount->isPositive();
        $hasDiscount = \Brick\Math\BigDecimal::of($sale->discount ?? 0)->isPositive();
        $canReceivePayment = $isCompleted && $hasOutstandingAmount;
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

        <section id="sale-summary" class="panel sale-summary" aria-label="Resumo da venda" data-live-region>
            <div class="sale-summary-main">
                <dl class="sale-facts">
                    <div>
                        <dt>Cliente</dt>
                        <dd class="wrap-anywhere"><a class="text-link" href="{{ route('customers.show', $sale->customer_id) }}">{{ $sale->customer->name }}</a></dd>
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
                    <dd><x-ui.payment-status :status="$paymentStatus" /></dd>
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

        <x-ui.data-table id="sale-items" title="Produtos da venda" :meta="$itemsMeta">
            <x-slot:filters>
                <x-ui.list-search label="Buscar produto da venda" placeholder="Nome ou código de barras" name="q_itens" :keep="['q_pagamentos', 'page']" />
            </x-slot:filters>
            <thead>
                <tr>
                    @foreach (['Produto', 'Quantidade', 'Preço unitário', 'Custo unitário da venda', 'Subtotal'] as $heading)
                        <th scope="col" @class(['money' => $loop->index >= 2])>{{ $heading }}</th>
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
                            <td class="money"><x-ui.money :value="$amount" /></td>
                        @endforeach
                    </tr>
                @empty
                    <x-ui.empty :columns="5">{{ request()->filled('q_itens') ? 'Nenhum produto desta venda corresponde à busca.' : 'Nenhum item registrado nesta venda.' }}</x-ui.empty>
                @endforelse
            </tbody>
            <tfoot class="table-totals">
                <tr>
                    <th scope="row" colspan="4">Subtotal</th>
                    <td class="money"><x-ui.money :value="$sale->subtotal" /></td>
                </tr>
                <tr>
                    <th scope="row" colspan="4">Desconto</th>
                    <td class="money">{{ $hasDiscount ? '− ' : '' }}<x-ui.money :value="$sale->discount" /></td>
                </tr>
                <tr class="table-grand-total">
                    <th scope="row" colspan="4">Total da venda</th>
                    <td class="money"><x-ui.money :value="$sale->total" /></td>
                </tr>
            </tfoot>
        </x-ui.data-table>

        <x-ui.data-table id="sale-payments" title="Pagamentos desta venda" :meta="$paymentsMeta" :paginator="$payments">
            <x-slot:filters>
                <x-ui.list-search label="Buscar pagamento" placeholder="Número, forma ou observação" name="q_pagamentos" :keep="['q_itens']" :disabled="$sale->payments_count === 0" />
            </x-slot:filters>
            <x-slot:actions>
                <span id="sale-payment-action" class="contents" data-live-region>
                    <button type="button" class="button-primary" data-dialog-open="sale-payment" @disabled(! $canReceivePayment)
                        @unless ($canReceivePayment) title="{{ $isCompleted ? 'Esta venda já está quitada.' : 'Vendas canceladas não recebem pagamentos.' }}" @endunless>Registrar pagamento</button>
                </span>
            </x-slot:actions>
            <thead>
                <tr>
                    <th scope="col">Pagamento</th>
                    <th scope="col">Data</th>
                    <th scope="col">Forma de pagamento</th>
                    <th scope="col" class="money">Valor abatido nesta venda</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <th scope="row">{{ $payment->id }}</th>
                        <td>{{ $payment->paid_on->format('d/m/Y') }}</td>
                        <td>{{ \App\Models\Payment::Methods[$payment->method] }}</td>
                        <td class="money"><x-ui.money :value="$payment->allocation->amount" /></td>
                    </tr>
                @empty
                    @if ($sale->payments_count === 0)
                        <x-ui.empty :columns="4" title="Nenhum pagamento vinculado a esta venda.">
                            {{ $hasOutstandingAmount ? 'Use “Registrar pagamento” para receber o saldo em aberto desta venda.' : 'Não há valores a receber nesta venda.' }}
                        </x-ui.empty>
                    @else
                        <x-ui.empty :columns="4">Nenhum pagamento corresponde à busca.</x-ui.empty>
                    @endif
                @endforelse
            </tbody>
        </x-ui.data-table>
    </div>

    <x-ui.dialog id="sale-payment" title="Registrar pagamento" :auto-open="$errors->any()" drawer inline-fallback>
        <div id="sale-payment-form" class="contents" data-live-region>
            @include('customers.payment-form', [
                'action' => route('sales.payments.store', $sale),
                'outstandingTotal' => (string) $outstandingAmount,
                'balanceLabel' => 'Saldo em aberto desta venda',
                'allocationHint' => 'O pagamento será destinado exclusivamente à venda #'.$sale->id.'.',
                'liveSubmit' => true,
                'settledMessage' => $isCompleted ? 'Esta venda está quitada.' : 'Vendas canceladas não recebem pagamentos.',
            ])
        </div>
    </x-ui.dialog>
@endsection
