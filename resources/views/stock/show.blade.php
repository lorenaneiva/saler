@extends('layout')

@section('title', 'Movimentações de estoque | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="grid gap-3">
            <a href="{{ route('stock.index') }}" class="w-fit text-sm text-emerald-800 underline">Voltar ao estoque</a>
            <h1 class="page-title wrap-anywhere">{{ $product->name }}</h1>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        <dl class="panel grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-600">Saldo atual</dt>
                <dd class="text-xl font-semibold">{{ $product->stock_quantity }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-600">Custo médio</dt>
                <dd class="text-xl font-semibold">R$ {{ number_format((float) $product->average_cost, 4, ',', '.') }}</dd>
            </div>
        </dl>

        @include('stock.movement-form')

        <section class="grid gap-3">
            <h2 class="text-xl font-semibold">Histórico de movimentações</h2>
            <div class="panel overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Movimentações de {{ $product->name }}</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                        <tr>
                            @foreach (['Data', 'Tipo', 'Origem', 'Quantidade', 'Custo unitário', 'Prejuízo', 'Responsável', 'Motivo e observação'] as $heading)
                                <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-4">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4">{{ $movement->quantity_delta > 0 ? 'Entrada' : 'Saída' }}</td>
                                <td class="px-4 py-4">
                                    {{ $movement->typeLabel() }}
                                    @if ($movement->purchaseItem)
                                        <a href="{{ route('purchases.show', $movement->purchaseItem->purchase_id) }}" class="text-emerald-800 underline">
                                            #{{ $movement->purchaseItem->purchase_id }}
                                        </a>
                                    @elseif ($movement->saleItem)
                                        <a href="{{ route('sales.show', $movement->saleItem->sale_id) }}" class="text-emerald-800 underline">
                                            #{{ $movement->saleItem->sale_id }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                    @if ($movement->adjustment)
                                        <div class="whitespace-nowrap text-xs text-slate-600">
                                            {{ $movement->adjustment->previous_quantity }} → {{ $movement->adjustment->counted_quantity }}
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ number_format((float) $movement->unit_cost, 4, ',', '.') }}</td>
                                @php($loss = $movement->lossValue())
                                <td class="whitespace-nowrap px-4 py-4">{{ $loss === null ? '—' : 'R$ '.str_replace('.', ',', $loss) }}</td>
                                <td class="px-4 py-4">{{ $movement->user->name }}</td>
                                <td class="min-w-32 wrap-anywhere px-4 py-4">
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
                            <tr><td colspan="8" class="px-4 py-6 text-slate-600">Nenhuma movimentação registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
        </section>
    </div>
@endsection
