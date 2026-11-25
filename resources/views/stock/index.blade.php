@extends('layout')

@section('title', 'Estoque | Saler')

@section('content')
    <x-ui.page-header title="Estoque" description="Consulte saldos, custos e movimentações por produto.">
        <x-slot:actions>
            <a href="{{ route('stock.reasons.index') }}" class="button-secondary">Motivos de ajuste</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table label="Saldo atual por produto">
        <thead>
            <tr>
                <th scope="col">Produto</th>
                <th scope="col" class="numeric">Saldo atual</th>
                <th scope="col" class="numeric">Custo médio</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <th scope="row" class="min-w-36 wrap-anywhere font-semibold">
                        <a href="{{ route('stock.show', $product) }}" class="text-emerald-800 underline">{{ $product->name }}</a>
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
                    <td class="numeric"><x-ui.money :value="$product->average_cost" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="3">Nenhum produto cadastrado.</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$products" />
@endsection
