@extends('layout')

@section('title', 'Movimentações de estoque | Saler')

@section('content')
    <div class="grid gap-4">
        <x-ui.page-header :title="$product->name" :back="route('stock.index')" back-label="Estoque">
            <x-slot:actions>
                <button type="button" class="button-primary" data-dialog-open="stock-movement">Registrar movimentação</button>
            </x-slot:actions>
        </x-ui.page-header>

        <dl class="panel grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-600">Saldo atual</dt>
                <dd class="text-xl font-semibold">{{ $product->stock_quantity }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-600">Custo médio</dt>
                <dd class="text-xl font-semibold"><x-ui.product-cost :product="$product" class="text-base" /></dd>
            </div>
        </dl>

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
        @include('stock.movement-form')
    </x-ui.dialog>
@endsection
