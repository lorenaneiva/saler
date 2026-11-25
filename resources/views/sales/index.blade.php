@extends('layout')

@section('title', 'Vendas | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Vendas</h1>
            <a href="{{ route('sales.create') }}" class="button-primary">Nova venda</a>
        </div>

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Vendas registradas</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        @foreach (['Venda', 'Cliente', 'Data', 'Total'] as $heading)
                            <th scope="col" class="px-4 py-3">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($sales as $sale)
                        <tr>
                            <th scope="row" class="px-4 py-4">
                                <a href="{{ route('sales.show', $sale) }}" class="text-emerald-800 underline">#{{ $sale->id }}</a>
                            </th>
                            <td class="px-4 py-4">{{ $sale->customer->name }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $sale->sold_on->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-4">R$ {{ str_replace('.', ',', $sale->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-slate-600">Nenhuma venda registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $sales->links() }}
    </div>
@endsection
