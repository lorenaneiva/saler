@extends('layout')

@section('title', 'Fornecedores | Saler')

@section('content')
    <x-ui.page-header title="Fornecedores" description="Consulte os contatos de compra e gerencie os fornecedores ativos.">
        <x-slot:actions>
            <a href="{{ route('suppliers.create') }}" class="button-primary">Novo fornecedor</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="suppliers" title="Cadastro de fornecedores" :paginator="$suppliers" :unit="['fornecedor', 'fornecedores']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar fornecedor" placeholder="Nome, contato ou representante" />
        </x-slot:filters>

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
                @php($editForm = 'supplier-'.$supplier->id)
                <tr data-inline-editor>
                    <th scope="row" class="min-w-36 wrap-anywhere font-semibold">
                        <x-ui.inline-field :form="$editForm" name="name" label="Nome" :value="$supplier->name" required maxlength="255" />
                    </th>
                    <td class="min-w-32 wrap-anywhere">
                        <x-ui.inline-field :form="$editForm" name="contact" label="Contato" :value="$supplier->contact" required maxlength="255" />
                    </td>
                    <td class="min-w-32 wrap-anywhere">
                        <x-ui.inline-field :form="$editForm" name="representative" label="Representante (opcional)" :value="$supplier->representative" maxlength="255" />
                    </td>
                    <td>
                        <x-ui.badge :tone="$supplier->is_active ? 'success' : 'neutral'">{{ $supplier->is_active ? 'Ativo' : 'Inativo' }}</x-ui.badge>
                    </td>
                    <td>
                        <div class="row-actions">
                            <x-ui.inline-actions :id="$editForm" :action="route('suppliers.update', $supplier)" :label="'Editar '.$supplier->name" />
                            <form data-inline-display method="POST" action="{{ $supplier->is_active ? route('suppliers.destroy', $supplier) : route('suppliers.activate', $supplier) }}"
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
                <x-ui.empty :columns="5">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhum fornecedor cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>
@endsection
