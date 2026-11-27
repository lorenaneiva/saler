@extends('layout')

@section('title', 'Estoque | Saler')

@section('content')
    <x-ui.page-header title="Estoque" description="Consulte saldos, custos e movimentações por produto.">
        <x-slot:actions>
            <a href="{{ route('stock.reasons.index') }}" class="button-secondary">Motivos de ajuste</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="stock" title="Saldo atual por produto" :paginator="$products" :unit="['produto', 'produtos']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar no estoque" placeholder="Nome ou código de barras" />
        </x-slot:filters>

        <thead>
            <tr>
                <x-ui.sort-header :sort="$sort" column="nome">Produto</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="saldo" class="numeric">Saldo atual</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="custo" class="money">Custo médio</x-ui.sort-header>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <th scope="row" class="min-w-36 wrap-anywhere font-semibold">
                        <a data-row-link href="{{ route('stock.show', $product) }}">{{ $product->name }}</a>
                        @unless ($product->is_active)
                            <x-ui.badge class="ml-2">Inativo</x-ui.badge>
                        @endunless
                    </th>
                    <td class="numeric">
                        @if ($product->stock_quantity <= 0)
                            <x-ui.badge tone="danger">Sem estoque</x-ui.badge>
                        @else
                            {{ $product->stock_quantity }}
                        @endif
                    </td>
                    <td class="money"><x-ui.product-cost :product="$product" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="3">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhum produto cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>
@endsection
