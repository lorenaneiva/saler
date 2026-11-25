@extends('layout')

@section('title', 'Produtos | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Produtos</h1>
            <a href="{{ route('products.create') }}" class="button-primary">Novo produto</a>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Catálogo de produtos</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Produto</th>
                        <th scope="col" class="px-4 py-3">Categoria</th>
                        <th scope="col" class="px-4 py-3">Fornecedor</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Preço de venda</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Custo médio</th>
                        <th scope="col" class="px-4 py-3">Margem</th>
                        <th scope="col" class="px-4 py-3">Estoque</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($products as $product)
                        @php($margin = $product->profitMargin())
                        <tr>
                            <th scope="row" class="min-w-36 break-all px-4 py-4 font-semibold">{{ $product->name }}</th>
                            <td class="px-4 py-4">{{ $product->category->name }}</td>
                            <td class="px-4 py-4">{{ $product->supplier?->name ?? $product->supplier_name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $product->average_cost, 4, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-4">{{ $margin === null ? '—' : number_format((float) $margin, 2, ',', '.').'%' }}</td>
                            <td class="px-4 py-4">{{ $product->stock_quantity }}</td>
                            <td class="px-4 py-4">
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-900' => $product->is_active, 'bg-slate-100 text-slate-700' => ! $product->is_active])>
                                    {{ $product->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('products.edit', $product) }}" aria-label="Editar {{ $product->name }}" class="underline">Editar</a>
                                    @if ($product->is_active)
                                        <form method="POST" action="{{ route('products.destroy', $product) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button aria-label="Desativar {{ $product->name }}" class="text-red-800 underline">Desativar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-slate-600">Nenhum produto cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
@endsection
