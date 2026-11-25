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
                    'Subtotal' => 'R$ '.str_replace('.', ',', $sale->subtotal),
                    'Desconto' => 'R$ '.str_replace('.', ',', $sale->discount),
                    'Total' => 'R$ '.str_replace('.', ',', $sale->total),
                ] as $label => $value)
                    <div>
                        <dt class="text-sm text-slate-600">{{ $label }}</dt>
                        <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            @if ($sale->notes)
                <p class="whitespace-pre-wrap wrap-anywhere text-sm text-slate-700">{{ $sale->notes }}</p>
            @endif
        </div>

        <x-ui.table label="Itens da venda">
                <thead>
                    <tr>
                        @foreach (['Produto', 'Quantidade', 'Preço unitário', 'Custo unitário da venda', 'Subtotal'] as $heading)
                            <th scope="col" @class(['whitespace-nowrap', 'numeric' => $loop->index >= 2])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 wrap-anywhere font-semibold">{{ $item->product->name }}</th>
                            <td>{{ $item->quantity }}</td>
                            @foreach ([$item->unit_price, $item->unit_cost, $item->subtotal] as $amount)
                                <td class="numeric"><x-ui.money :value="$amount" /></td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
    </div>
@endsection
