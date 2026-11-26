@extends('layout')

@section('title', 'Produtos | Saler')

@section('content')
    <x-ui.page-header title="Produtos" description="Consulte preços, fornecedores e disponibilidade.">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" class="button-primary"><span aria-hidden="true">＋</span> Novo produto</a>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="GET" action="{{ route('products.index') }}" class="panel filter-bar" role="search">
        <label>
            Buscar produto
            <input name="q" type="search" value="{{ $filters['search'] }}" maxlength="100" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Digite o nome ou leia o código de barras" class="field">
        </label>
        <label>
            Categoria
            <select name="categoria" class="field">
                <option value="">Todas</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Fornecedor
            <select name="fornecedor" class="field">
                <option value="">Todos</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected($filters['supplier'] === $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Situação
            <select name="situacao" class="field">
                <option value="">Todas</option>
                <option value="ativos" @selected($filters['status'] === 'ativos')>Ativos</option>
                <option value="inativos" @selected($filters['status'] === 'inativos')>Inativos</option>
            </select>
        </label>
        <div class="page-actions">
            <button class="button-secondary">Filtrar</button>
            @if (array_filter($filters))
                <a href="{{ route('products.index') }}" class="button-quiet">Limpar</a>
            @endif
        </div>
    </form>

    <x-ui.table label="Catálogo de produtos">
        <thead>
            <tr>
                <th scope="col">Produto</th>
                <th scope="col">Categoria / fornecedor</th>
                <th scope="col" class="numeric">Preço</th>
                <th scope="col" class="numeric">Custo médio</th>
                <th scope="col" class="numeric">Margem</th>
                <th scope="col">Estoque</th>
                <th scope="col">Situação</th>
                <th scope="col" class="text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php($margin = $product->profitMargin())
                <tr>
                    <th scope="row" class="min-w-36 wrap-anywhere font-semibold">
                        {{ $product->name }}
                        @if ($product->barcode)
                            <span class="block text-xs font-normal text-slate-600">{{ $product->barcode }}</span>
                        @endif
                    </th>
                    <td class="min-w-32 wrap-anywhere">
                        {{ $product->category->name }}
                        <span class="block text-xs text-slate-600">{{ $product->supplier?->name ?? $product->supplier_name ?? '—' }}</span>
                    </td>
                    <td class="numeric font-semibold"><x-ui.money :value="$product->sale_price" /></td>
                    <td class="numeric"><x-ui.money :value="$product->average_cost" /></td>
                    <td class="numeric">{{ $margin === null ? '—' : number_format((float) $margin, 2, ',', '.').'%' }}</td>
                    <td class="whitespace-nowrap">
                        @if ($product->stock_quantity <= 0)
                            <x-ui.badge tone="danger">Sem estoque</x-ui.badge>
                        @elseif ($product->stock_quantity === 1)
                            <x-ui.badge tone="warning">Última unidade</x-ui.badge>
                        @else
                            {{ $product->stock_quantity }} un.
                        @endif
                    </td>
                    <td><x-ui.badge :tone="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge></td>
                    <td>
                        <div class="row-actions">
                            <a data-row-link href="{{ route('products.edit', $product) }}" aria-label="Editar {{ $product->name }}" class="button-quiet button-small">Editar</a>
                            @if ($product->is_active)
                                <form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Desativar o produto {{ $product->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button aria-label="Desativar {{ $product->name }}" class="button-quiet danger button-small">Desativar</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="8">{{ array_filter($filters) ? 'Nenhum produto encontrado para estes filtros.' : 'Nenhum produto cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$products" />
@endsection
