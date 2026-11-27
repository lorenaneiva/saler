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
            ] as $label => $value)
                <div class="grid gap-1">
                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                    <dd class="wrap-anywhere font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="grid gap-1">
                <dt class="text-sm font-medium text-slate-600">Total</dt>
                <dd class="wrap-anywhere font-semibold"><x-ui.money :value="$purchase->total" /></dd>
            </div>
        </dl>

        @php($itemsUnit = $purchase->items_count === 1 ? 'item' : 'itens')
        <x-ui.data-table id="purchase-items" title="Itens da compra"
            :meta="request()->filled('q_itens') ? $purchase->items->count().' de '.$purchase->items_count.' '.$itemsUnit : $purchase->items_count.' '.$itemsUnit">
            <x-slot:filters>
                <x-ui.list-search label="Buscar item da compra" placeholder="Nome ou código de barras" name="q_itens" />
            </x-slot:filters>
                <thead>
                    <tr>
                        <x-ui.sort-header :sort="$itemsSort" column="produto">Produto</x-ui.sort-header>
                        <x-ui.sort-header :sort="$itemsSort" column="quantidade">Quantidade</x-ui.sort-header>
                        <x-ui.sort-header :sort="$itemsSort" column="custo" class="money">Custo unitário</x-ui.sort-header>
                        <x-ui.sort-header :sort="$itemsSort" column="desconto" class="money">Desconto</x-ui.sort-header>
                        <x-ui.sort-header :sort="$itemsSort" column="total" class="money">Total do item</x-ui.sort-header>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchase->items as $item)
                        <tr>
                            <th scope="row" class="min-w-36 font-semibold">
                                <a data-row-link href="{{ route('products.show', $item->product_id) }}">{{ $item->product->name }}</a>
                            </th>
                            <td>{{ $item->quantity }}</td>
                            @foreach ([$item->unit_cost, $item->discount, $item->total] as $amount)
                                <td class="money"><x-ui.money :value="$amount" /></td>
                            @endforeach
                        </tr>
                    @empty
                        <x-ui.empty :columns="5">{{ request()->filled('q_itens') ? 'Nenhum item desta compra corresponde à busca.' : 'Nenhum item registrado nesta compra.' }}</x-ui.empty>
                    @endforelse
                </tbody>
        </x-ui.data-table>

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
