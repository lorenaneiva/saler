@extends('layout')

@section('title', 'Vendas | Saler')

@section('content')
    <x-ui.page-header title="Vendas" description="Consulte vendas registradas e seus detalhes.">
        <x-slot:actions>
            <a href="{{ route('sales.create') }}" class="button-primary">Nova venda</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="sales" title="Vendas registradas" :paginator="$sales" :unit="['venda', 'vendas']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar venda" placeholder="Número, cliente ou produto" />
        </x-slot:filters>

        <thead>
            <tr>
                @foreach (['Venda', 'Cliente', 'Data', 'Total', 'Situação financeira'] as $heading)
                    <th scope="col" @class(['money' => $heading === 'Total'])>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <th scope="row">
                        <a data-row-link href="{{ route('sales.show', $sale) }}">{{ $sale->id }}</a>
                    </th>
                    <td class="wrap-anywhere"><a class="cell-link" href="{{ route('customers.show', $sale->customer_id) }}">{{ $sale->customer->name }}</a></td>
                    <td class="whitespace-nowrap">{{ $sale->sold_on->format('d/m/Y') }}</td>
                    <td class="money"><x-ui.money :value="$sale->total" /></td>
                    <td><x-ui.payment-status :status="$sale->paymentStatus()" /></td>
                </tr>
            @empty
                <x-ui.empty :columns="5">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhuma venda registrada.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>
@endsection
