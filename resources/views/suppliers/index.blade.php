@extends('layout')

@section('title', 'Fornecedores | Saler')

@section('content')
    <x-ui.page-header title="Fornecedores" description="Consulte os contatos de compra e gerencie os fornecedores ativos.">
        <x-slot:actions>
            <a href="{{ route('suppliers.create') }}" class="button-primary">Novo fornecedor</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table label="Cadastro de fornecedores">
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">Contato</th>
                <th scope="col">Representante</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <th scope="row" class="min-w-36 wrap-anywhere font-semibold">{{ $supplier->name }}</th>
                    <td class="min-w-32 wrap-anywhere">{{ $supplier->contact }}</td>
                    <td class="min-w-32 wrap-anywhere">{{ $supplier->representative ?? '—' }}</td>
                    <td>
                        <x-ui.badge :tone="$supplier->is_active ? 'success' : 'neutral'">{{ $supplier->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('suppliers.edit', $supplier) }}" aria-label="Editar {{ $supplier->name }}" class="button-quiet button-small">Editar</a>
                            <form method="POST" action="{{ $supplier->is_active ? route('suppliers.destroy', $supplier) : route('suppliers.activate', $supplier) }}"
                                @if ($supplier->is_active) data-confirm="Desativar o fornecedor {{ $supplier->name }}?" @endif>
                                @csrf
                                @method($supplier->is_active ? 'DELETE' : 'PATCH')
                                <button @class(['button-quiet button-small', 'danger' => $supplier->is_active])
                                    aria-label="{{ $supplier->is_active ? 'Desativar' : 'Ativar' }} {{ $supplier->name }}">
                                    {{ $supplier->is_active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="5">Nenhum fornecedor cadastrado.</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$suppliers" />
@endsection
