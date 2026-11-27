@extends('layout')

@section('title', 'Produtos | Saler')

@section('content')
    <x-ui.page-header title="Produtos" description="Consulte preços, fornecedores e disponibilidade.">
        <x-slot:actions>
            <a href="{{ route('products.create') }}" data-product-open class="button-primary"><span aria-hidden="true">＋</span> Novo produto</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="products" title="Catálogo de produtos" :paginator="$products" :unit="['produto', 'produtos']" data-product-list>
        <x-slot:filters>
            <x-ui.list-search label="Buscar produto" placeholder="Nome ou código de barras" :active="(bool) array_filter($filters)" autocapitalize="none" spellcheck="false">
                <x-ui.table-filter label="Categoria">
                    <select name="categoria" class="field">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-ui.table-filter>
                <x-ui.table-filter label="Fornecedor">
                    <select name="fornecedor" class="field">
                        <option value="">Todos</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected($filters['supplier'] === $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </x-ui.table-filter>
                <x-ui.table-filter label="Situação">
                    <select name="situacao" class="field">
                        <option value="">Todas</option>
                        <option value="ativos" @selected($filters['status'] === 'ativos')>Ativos</option>
                        <option value="inativos" @selected($filters['status'] === 'inativos')>Inativos</option>
                    </select>
                </x-ui.table-filter>
            </x-ui.list-search>
        </x-slot:filters>
        <thead>
            <tr>
                <x-ui.sort-header :sort="$sort" column="nome">Produto</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="categoria">Categoria / fornecedor</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="preco" class="money">Preço</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="custo" class="money">Custo médio</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="margem" class="numeric">Margem</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="estoque">Estoque</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="situacao">Situação</x-ui.sort-header>
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
                    <td class="money font-semibold"><x-ui.money :value="$product->sale_price" /></td>
                    <td class="money"><x-ui.product-cost :product="$product" /></td>
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
                            <a href="{{ route('products.edit', $product) }}" data-product-open class="button-quiet button-small" aria-label="Editar {{ $product->name }}">Editar</a>
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
    </x-ui.data-table>

    <div data-product-feedback class="product-feedback notice notice-success" role="status" aria-live="polite" hidden></div>

    <x-ui.dialog id="product-editor" title="Editar produto" drawer class="product-editor">
        <div data-product-editor-content></div>
    </x-ui.dialog>

    <x-ui.dialog id="discard-product-changes" title="Descartar alterações?">
        <p class="text-sm">As alterações deste produto ainda não foram salvas.</p>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="button-secondary" data-dialog-close autofocus>Continuar editando</button>
            <button type="button" class="button-danger" data-product-discard>Descartar alterações</button>
        </div>
    </x-ui.dialog>
@endsection
