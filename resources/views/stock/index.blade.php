@extends('layout')

@section('title', 'Estoque | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Estoque</h1>
            <a href="{{ route('stock.reasons.index') }}" class="button-secondary">Motivos de ajuste</a>
        </div>

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Saldo atual por produto</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Produto</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Saldo atual</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Custo médio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($products as $product)
                        <tr>
                            <th scope="row" class="min-w-36 wrap-anywhere px-4 py-4 font-semibold">
                                <a href="{{ route('stock.show', $product) }}" class="text-emerald-800 underline">{{ $product->name }}</a>
                                @unless ($product->is_active)
                                    <span class="ml-2 text-xs font-normal text-slate-600">Inativo</span>
                                @endunless
                            </th>
                            <td class="px-4 py-4">{{ $product->stock_quantity }}</td>
                            <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $product->average_cost, 4, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-slate-600">Nenhum produto cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
    </div>
@endsection
