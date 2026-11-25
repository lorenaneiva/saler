@extends('layout')

@section('title', 'Detalhes do cliente | Saler')

@section('content')
    <div class="grid max-w-3xl gap-6">
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

        <dl class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            @foreach (['Telefone' => $customer->phone, 'E-mail' => $customer->email, 'CPF' => $customer->cpf, 'CEP' => $customer->postal_code] as $label => $value)
                <div class="grid gap-1">
                    <dt class="text-sm font-medium text-slate-600">{{ $label }}</dt>
                    <dd class="wrap-anywhere">{{ $value ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endsection
