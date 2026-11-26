@extends('layout')

@section('title', 'Detalhes da compra | Saler')

@section('content')
    <div class="grid gap-4">
        <x-ui.page-header :title="'Compra #'.$purchase->id" :back="route('purchases.index')" back-label="Compras" />
        @error('purchase')
            <x-ui.alert tone="error">{{ $message }}</x-ui.alert>
        @enderror

        <dl class="panel grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                'Fornecedor' => $purchase->supplier->name,
                'Data da compra' => $purchase->purchased_on->format('d/m/Y'),
                'Total' => 'R$ '.number_format((float) $purchase->total, 2, ',', '.'),
            ] as $label => $value)
                <div class="grid gap-1">
                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                    <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <x-ui.list-search label="Buscar item" placeholder="Nome ou código de barras" name="q_itens" />
        <x-ui.table label="Itens da compra">
                <thead>
                    <tr>
                        @foreach (['Produto', 'Quantidade', 'Custo unitário', 'Desconto', 'Total do item'] as $heading)
                            <th scope="col" @class(['whitespace-nowrap', 'numeric' => $loop->index >= 2])>{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchase->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 font-semibold">
                                <a data-row-link href="{{ route('stock.show', $item->product_id) }}">{{ $item->product->name }}</a>
                            </th>
                            <td>{{ $item->quantity }}</td>
                            @foreach ([$item->unit_cost, $item->discount, $item->total] as $amount)
                                <td class="numeric"><x-ui.money :value="$amount" /></td>
                            @endforeach
                        </tr>
                    @empty
                        <x-ui.empty :columns="5">Nenhum item encontrado.</x-ui.empty>
                    @endforelse
                </tbody>
            </x-ui.table>

        @if ($purchase->status !== \App\Models\Purchase::Cancelled)
            <div class="flex flex-wrap gap-3">
                @if ($purchase->status === \App\Models\Purchase::Pending)
                    <form method="POST" action="{{ route('purchases.confirm', $purchase) }}">
                        @csrf
                        <button class="button-primary">Confirmar e atualizar estoque</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}"
                    @if ($purchase->status === \App\Models\Purchase::Confirmed)
                        data-confirm="Estornar as entradas desta compra?"
                    @endif>
                    @csrf
                    <button class="button-quiet danger">{{ $purchase->status === \App\Models\Purchase::Confirmed ? 'Estornar compra' : 'Cancelar compra' }}</button>
                </form>
            </div>
        @endif
    </div>
@endsection
