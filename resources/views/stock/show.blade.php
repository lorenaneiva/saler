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
                <dd class="text-xl font-semibold"><x-ui.money :value="$product->average_cost" /></dd>
            </div>
        </dl>

        <section class="grid gap-3">
            <h2 class="text-base font-semibold">Histórico de movimentações</h2>
            <x-ui.list-search label="Buscar movimentação" placeholder="Responsável, motivo ou observação" />
            <x-ui.table label="Histórico de movimentações de estoque">
                    <thead>
                        <tr>
                            @foreach (['Data', 'Tipo', 'Origem', 'Quantidade', 'Custo unitário', 'Prejuízo', 'Responsável', 'Motivo e observação'] as $heading)
                                <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                            @endforeach
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
                                        <a data-row-link href="{{ route('purchases.show', $movement->purchaseItem->purchase_id) }}" class="text-brand underline">
                                            #{{ $movement->purchaseItem->purchase_id }}
                                        </a>
                                    @elseif ($movement->saleItem)
                                        <a data-row-link href="{{ route('sales.show', $movement->saleItem->sale_id) }}" class="text-brand underline">
                                            #{{ $movement->saleItem->sale_id }}
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                    @if ($movement->adjustment)
                                        <div class="whitespace-nowrap text-xs text-slate-600">
                                            {{ $movement->adjustment->previous_quantity }} → {{ $movement->adjustment->counted_quantity }}
                                        </div>
                                    @endif
                                </td>
                                <td class="numeric"><x-ui.money :value="$movement->unit_cost" /></td>
                                @php($loss = $movement->lossValue())
                                <td class="whitespace-nowrap">{{ $loss === null ? '—' : 'R$ '.str_replace('.', ',', $loss) }}</td>
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
                </x-ui.table>
            <x-ui.pagination :paginator="$movements" />
        </section>
    </div>

    <x-ui.dialog id="stock-movement" title="Registrar movimentação" :auto-open="$errors->any()" inline-fallback>
        @include('stock.movement-form')
    </x-ui.dialog>
@endsection
