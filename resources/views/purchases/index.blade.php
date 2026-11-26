@extends('layout')

@section('title', 'Compras | Saler')

@section('content')
    <x-ui.page-header title="Compras" description="Consulte as entradas de mercadorias por fornecedor.">
        <x-slot:actions>
            <a href="{{ route('purchases.create') }}" class="button-primary">Nova compra</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="purchases" title="Compras de mercadorias" :paginator="$purchases" :unit="['compra', 'compras']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar compra" placeholder="Número, fornecedor ou produto" />
        </x-slot:filters>

        <thead>
            <tr>
                <th scope="col">Compra</th>
                <th scope="col">Fornecedor</th>
                <th scope="col">Data</th>
                <th scope="col" class="money">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <th scope="row" class="font-semibold">
                        <a data-row-link href="{{ route('purchases.show', $purchase) }}">{{ $purchase->id }}</a>
                    </th>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td class="whitespace-nowrap">{{ $purchase->purchased_on->format('d/m/Y') }}</td>
                    <td class="money"><x-ui.money :value="$purchase->total" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="4">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhuma compra registrada.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>
@endsection
