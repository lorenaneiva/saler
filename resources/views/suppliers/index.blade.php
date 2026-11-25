@extends('layout')

@section('title', 'Fornecedores | Saler')

@section('content')
    <div class="grid gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="page-title">Fornecedores</h1>
            <a href="{{ route('suppliers.create') }}" class="button-primary">Novo fornecedor</a>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        <div class="panel overflow-x-auto">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Cadastro de fornecedores</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Nome</th>
                        <th scope="col" class="px-4 py-3">Contato</th>
                        <th scope="col" class="px-4 py-3">Representante</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <th scope="row" class="min-w-36 wrap-anywhere px-4 py-4 font-semibold">{{ $supplier->name }}</th>
                            <td class="min-w-32 wrap-anywhere px-4 py-4">{{ $supplier->contact }}</td>
                            <td class="min-w-32 wrap-anywhere px-4 py-4">{{ $supplier->representative ?? '—' }}</td>
                            <td class="px-4 py-4">
                                <span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-900' => $supplier->is_active, 'bg-slate-100 text-slate-700' => ! $supplier->is_active])>
                                    {{ $supplier->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" aria-label="Editar {{ $supplier->name }}" class="underline">Editar</a>
                                    <form method="POST" action="{{ $supplier->is_active ? route('suppliers.destroy', $supplier) : route('suppliers.activate', $supplier) }}">
                                        @csrf
                                        @method($supplier->is_active ? 'DELETE' : 'PATCH')
                                        <button @class(['underline', 'text-red-800' => $supplier->is_active, 'text-emerald-800' => ! $supplier->is_active])
                                            aria-label="{{ $supplier->is_active ? 'Desativar' : 'Ativar' }} {{ $supplier->name }}">
                                            {{ $supplier->is_active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-slate-600">Nenhum fornecedor cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $suppliers->links() }}
    </div>
@endsection
