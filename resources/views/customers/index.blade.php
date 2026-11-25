@extends('layout')

@section('title', 'Clientes | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Clientes</h1>
            <a href="{{ route('customers.create') }}" class="button-primary">Novo cliente</a>
        </div>

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Cadastro de clientes</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nome</th>
                        <th scope="col" class="px-4 py-3">Telefone</th>
                        <th scope="col" class="px-4 py-3">E-mail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($customers as $customer)
                        <tr>
                            <th scope="row" class="min-w-36 px-4 py-4 font-semibold">
                                <a href="{{ route('customers.show', $customer) }}" class="text-emerald-800 underline">
                                    {{ $customer->name }}
                                </a>
                            </th>
                            <td class="whitespace-nowrap px-4 py-4">{{ $customer->phone }}</td>
                            <td class="px-4 py-4">{{ $customer->email ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-slate-600">Nenhum cliente cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    </div>
@endsection
