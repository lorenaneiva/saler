@extends('layout')

@section('title', 'Clientes | Saler')

@section('content')
    <x-ui.page-header title="Clientes" description="Consulte contatos, compras e saldos.">
        <x-slot:actions>
            <a href="{{ route('customers.create') }}" class="button-primary">Novo cliente</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="customers" title="Cadastro de clientes" :paginator="$customers" :unit="['cliente', 'clientes']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar cliente" placeholder="Nome, telefone, e-mail ou CPF" />
        </x-slot:filters>

        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">Telefone</th>
                <th scope="col">E-mail</th>
                <th scope="col" class="text-right">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                @php($editForm = 'customer-'.$customer->id)
                <tr data-inline-editor>
                    <th scope="row" class="min-w-36 font-semibold">
                        <x-ui.inline-field :form="$editForm" name="name" label="Nome" :value="$customer->name" required maxlength="255" autocomplete="name">
                            <a data-row-link href="{{ route('customers.show', $customer) }}">
                                {{ $customer->name }}
                            </a>
                        </x-ui.inline-field>
                    </th>
                    <td class="whitespace-nowrap">
                        <x-ui.inline-field :form="$editForm" name="phone" label="Telefone" :value="$customer->phone" type="tel" required maxlength="30" autocomplete="tel" data-mask="phone">
                            <x-ui.contact :value="$customer->phone" kind="phone" />
                        </x-ui.inline-field>
                    </td>
                    <td>
                        <x-ui.inline-field :form="$editForm" name="email" label="E-mail (opcional)" :value="$customer->email" type="email" maxlength="255" autocomplete="email" />
                    </td>
                    <td>
                        <div class="row-actions">
                            <x-ui.inline-actions :id="$editForm" :action="route('customers.update', $customer)" :label="'Editar '.$customer->name" />
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="4">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhum cliente cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>
@endsection
