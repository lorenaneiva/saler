@extends('layout')

@section('title', 'Detalhes da venda | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="grid gap-3">
            <a href="{{ route('sales.index') }}" class="w-fit text-sm text-emerald-800 underline">Voltar às vendas</a>
            <h1 class="page-title">Venda #{{ $sale->id }}</h1>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

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

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Itens da venda</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        @foreach (['Produto', 'Quantidade', 'Preço unitário', 'Custo unitário da venda', 'Subtotal'] as $heading)
                            <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($sale->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 wrap-anywhere px-4 py-4 font-semibold">{{ $item->product->name }}</th>
                            <td class="px-4 py-4">{{ $item->quantity }}</td>
                            @foreach ([$item->unit_price, $item->unit_cost, $item->subtotal] as $amount)
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ str_replace('.', ',', $amount) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
