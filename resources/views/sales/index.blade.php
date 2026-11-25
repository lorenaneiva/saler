@extends('layout')

@section('title', 'Vendas | Saler')

@section('content')
    <x-ui.page-header title="Vendas" description="Consulte vendas registradas e seus detalhes.">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="button-primary">Nova venda</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table label="Vendas registradas">
        <thead>
            <tr>
                @foreach (['Venda', 'Cliente', 'Data', 'Total'] as $heading)
                    <th scope="col">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <th scope="row">
                        <a href="{{ route('sales.show', $sale) }}" class="text-emerald-800 underline">#{{ $sale->id }}</a>
                    </th>
                    <td>{{ $sale->customer->name }}</td>
                    <td class="whitespace-nowrap">{{ $sale->sold_on->format('d/m/Y') }}</td>
                    <td class="numeric"><x-ui.money :value="$sale->total" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="4">Nenhuma venda registrada.</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$sales" />
@endsection
