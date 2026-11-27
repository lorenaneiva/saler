@extends('layout')

@section('title', 'Detalhes do produto | Saler')

@section('content')
    <div class="grid gap-4">
        <div id="product-overview" class="grid gap-4" data-product-refresh>
            <x-ui.page-header :title="$product->name" :back="route('products.index')" back-label="Produtos">
                <x-slot:actions>
                    <a href="{{ route('products.edit', $product) }}" data-product-open class="button-secondary">Editar produto</a>
                    <button type="button" class="button-primary" data-dialog-open="stock-movement">Registrar movimentação</button>
                </x-slot:actions>
            </x-ui.page-header>

            <section class="panel product-summary" aria-label="Resumo do produto">
                <dl class="product-facts">
                    <div>
                        <dt>Categoria</dt>
                        <dd><a class="text-link" href="{{ route('products.index', ['categoria' => $product->category_id]) }}">{{ $product->category->name }}</a></dd>
                    </div>
                    <div>
                        <dt>Fornecedor</dt>
                        <dd>{{ $product->supplier?->name ?? $product->supplier_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Código de barras</dt>
                        <dd class="tabular-nums">{{ $product->barcode ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt>Situação</dt>
                        <dd><x-ui.badge :tone="$product->is_active ? 'success' : 'neutral'">{{ $product->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge></dd>
                    </div>
                </dl>
                <dl class="product-figures">
                    <div>
                        <dt>Preço de venda</dt>
                        <dd><x-ui.money :value="$product->sale_price" /></dd>
                    </div>
                    <div>
                        <dt>Custo médio</dt>
                        <dd><x-ui.product-cost :product="$product" /></dd>
                    </div>
                    <div>
                        <dt>Margem de lucro</dt>
                        <dd><x-ui.profit-margin :product="$product" /></dd>
                    </div>
                    <div>
                        <dt>Saldo em estoque</dt>
                        <dd>
                            @if ($product->stock_quantity <= 0)
                                <x-ui.badge tone="danger">Sem estoque</x-ui.badge>
                            @else
                                {{ $product->stock_quantity }} un.
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Valor em estoque (custo)</dt>
                        <dd>
                            @if (($stockValue = $product->stockValue()) === null)
                                <span class="text-unknown">Sem custo registrado</span>
                            @else
                                <x-ui.money :value="$stockValue" />
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <x-ui.data-table id="stock-movements" title="Histórico de movimentações" label="Histórico de movimentações de estoque" :paginator="$movements" :unit="['movimentação', 'movimentações']">
            <x-slot:filters>
                <x-ui.list-search label="Buscar movimentação" placeholder="Responsável, motivo ou observação" />
            </x-slot:filters>
                    <thead>
                        <tr>
                            <x-ui.sort-header :sort="$sort" column="data">Data</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="tipo">Tipo</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="origem">Origem</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="quantidade" class="numeric">Quantidade</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="custo" class="money">Custo unitário</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="prejuizo" class="money">Prejuízo</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="responsavel">Responsável</x-ui.sort-header>
                            <x-ui.sort-header :sort="$sort" column="motivo">Motivo e observação</x-ui.sort-header>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->quantity_delta > 0 ? 'Entrada' : 'Saída' }}</td>
                                <td>
                                    {{ $movement->typeLabel() }}
                                    @if ($movement->purchaseItem)
                                        <a data-row-link href="{{ route('purchases.show', $movement->purchaseItem->purchase_id) }}">
                                            {{ $movement->purchaseItem->purchase_id }}
                                        </a>
                                    @elseif ($movement->saleItem)
                                        <a data-row-link href="{{ route('sales.show', $movement->saleItem->sale_id) }}">
                                            {{ $movement->saleItem->sale_id }}
                                        </a>
                                    @endif
                                </td>
                                <td class="numeric">
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                    @if ($movement->adjustment)
                                        <div class="whitespace-nowrap text-xs text-slate-600">
                                            {{ $movement->adjustment->previous_quantity }} → {{ $movement->adjustment->counted_quantity }}
                                        </div>
                                    @endif
                                </td>
                                <td class="money"><x-ui.money :value="$movement->unit_cost" /></td>
                                @php($loss = $movement->lossValue())
                                <td class="money">
                                    @if ($loss === null)
                                        —
                                    @else
                                        <x-ui.money :value="$loss" />
                                    @endif
                                </td>
                                <td>{{ $movement->user->name }}</td>
                                <td class="min-w-32 wrap-anywhere">
                                    @if ($movement->adjustment)
                                        <div class="font-medium">{{ $movement->adjustment->reason->name }}</div>
                                    @endif
                                    @if ($movement->justification)
                                        <div>{{ $movement->justification }}</div>
                                    @elseif (! $movement->adjustment)
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty :columns="8">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhuma movimentação registrada.' }}</x-ui.empty>
                        @endforelse
                    </tbody>
        </x-ui.data-table>
    </div>

    <x-ui.dialog id="stock-movement" title="Registrar movimentação" :auto-open="$errors->any()" inline-fallback>
        @include('products.movement-form')
    </x-ui.dialog>

    @include('products.editor-dialogs')
@endsection
