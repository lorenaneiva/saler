@extends('layout')

@section('title', 'Detalhes da compra | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="grid gap-3">
            <a href="{{ route('purchases.index') }}" class="w-fit text-sm text-emerald-800 underline">Voltar às compras</a>
            <h1 class="page-title">Compra #{{ $purchase->id }}</h1>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif
        @error('purchase')
            <p role="alert" class="rounded-lg bg-red-100 px-4 py-3 text-red-900">{{ $message }}</p>
        @enderror

        <dl class="panel grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'Fornecedor' => $purchase->supplier->name,
                'Data da compra' => $purchase->purchased_on->format('d/m/Y'),
                'Status' => $purchase->statusLabel(),
                'Total' => 'R$ '.number_format((float) $purchase->total, 2, ',', '.'),
            ] as $label => $value)
                <div class="grid gap-1">
                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                    <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Itens da compra</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        @foreach (['Produto', 'Quantidade', 'Custo unitário', 'Desconto', 'Total do item'] as $heading)
                            <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($purchase->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 px-4 py-4 font-semibold">{{ $item->product->name }}</th>
                            <td class="px-4 py-4">{{ $item->quantity }}</td>
                            @foreach ([$item->unit_cost, $item->discount, $item->total] as $amount)
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $amount, 2, ',', '.') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($purchase->status === \App\Models\Purchase::Pending)
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('purchases.confirm', $purchase) }}">
                    @csrf
                    <button class="button-primary">Confirmar e atualizar estoque</button>
                </form>
                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}">
                    @csrf
                    <button class="button-secondary">Cancelar compra</button>
                </form>
            </div>
        @endif
    </div>
@endsection
