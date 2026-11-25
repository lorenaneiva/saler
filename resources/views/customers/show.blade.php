@extends('layout')

@section('title', 'Detalhes do cliente | Saler')

@section('content')
    <div class="grid max-w-5xl gap-6">
        <div class="grid gap-4">
            <a href="{{ route('customers.index') }}" class="w-fit text-sm text-emerald-800 underline">Voltar aos clientes</a>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="page-title wrap-anywhere">{{ $customer->name }}</h1>
                <a href="{{ route('customers.edit', $customer) }}" class="button-primary">Editar cliente</a>
            </div>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        <dl class="panel border-emerald-200 bg-emerald-50 p-5 sm:p-6">
            <dt class="text-sm font-medium text-emerald-900">Saldo em aberto</dt>
            <dd class="mt-1 text-3xl font-semibold text-emerald-950">R$ {{ str_replace('.', ',', $outstandingTotal) }}</dd>
        </dl>

        <dl class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            @foreach (['Telefone' => $customer->phone, 'E-mail' => $customer->email, 'CPF' => $customer->cpf, 'CEP' => $customer->postal_code] as $label => $value)
                <div class="grid gap-1">
                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                    <dd class="wrap-anywhere">{{ $value ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>

        @include('customers.payment-form')

        <section class="grid gap-3">
            <h2 class="text-xl font-semibold">Histórico de compras</h2>
            <div class="panel overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                        <tr>
                            @foreach (['Compra', 'Data', 'Total', 'Pago', 'Em aberto', 'Situação'] as $heading)
                                <th scope="col" class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($sales as $sale)
                            <tr>
                                <th scope="row" class="px-4 py-4">
                                    <a href="{{ route('sales.show', $sale) }}" class="text-emerald-800 underline">#{{ $sale->id }}</a>
                                </th>
                                <td class="whitespace-nowrap px-4 py-4">{{ $sale->sold_on->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ str_replace('.', ',', $sale->total) }}</td>
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ str_replace('.', ',', (string) $sale->paidAmount()) }}</td>
                                <td class="whitespace-nowrap px-4 py-4">R$ {{ str_replace('.', ',', (string) $sale->outstandingAmount()) }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $sale->paymentStatus() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-slate-600">Nenhuma compra registrada para este cliente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sales->links() }}
        </section>

        @include('customers.payment-history')
    </div>
@endsection
