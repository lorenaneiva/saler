@extends('layout')

@section('title', 'Compras | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Compras</h1>
            <a href="{{ route('purchases.create') }}" class="button-primary">Nova compra</a>
        </div>

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Compras de mercadorias</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Compra</th>
                        <th scope="col" class="px-4 py-3">Fornecedor</th>
                        <th scope="col" class="px-4 py-3">Data</th>
                        <th scope="col" class="px-4 py-3">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($purchases as $purchase)
                        <tr>
                            <th scope="row" class="px-4 py-4 font-semibold">
                                <a href="{{ route('purchases.show', $purchase) }}" class="text-emerald-800 underline">#{{ $purchase->id }}</a>
                            </th>
                            <td class="px-4 py-4">{{ $purchase->supplier->name }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $purchase->purchased_on->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $purchase->total, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-slate-600">Nenhuma compra registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $purchases->links() }}
    </div>
@endsection
