@extends('layout')

@section('title', 'Compras | Saler')

@section('content')
    <x-ui.page-header title="Compras" description="Consulte as entradas de mercadorias por fornecedor.">
        <x-slot:actions>
            <a href="{{ route('purchases.create') }}" class="button-primary">Nova compra</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table label="Compras de mercadorias">
        <thead>
            <tr>
                <th scope="col">Compra</th>
                <th scope="col">Fornecedor</th>
                <th scope="col">Data</th>
                <th scope="col" class="numeric">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <th scope="row" class="px-4 py-4 font-semibold">
                        <a href="{{ route('purchases.show', $purchase) }}" class="text-emerald-800 underline">#{{ $purchase->id }}</a>
                    </th>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td class="whitespace-nowrap">{{ $purchase->purchased_on->format('d/m/Y') }}</td>
                    <td class="numeric"><x-ui.money :value="$purchase->total" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="4">Nenhuma compra registrada.</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$purchases" />
@endsection
