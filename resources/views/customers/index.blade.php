@extends('layout')

@section('title', 'Clientes | Saler')

@section('content')
    <x-ui.page-header title="Clientes" description="Consulte contatos, compras e saldos.">
        <x-slot:actions>
            <a href="{{ route('customers.create') }}" class="button-primary">Novo cliente</a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.list-search label="Buscar cliente" placeholder="Nome, telefone, e-mail ou CPF" />

    <x-ui.table label="Cadastro de clientes">
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">Telefone</th>
                <th scope="col">E-mail</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <th scope="row" class="min-w-36 font-semibold">
                        <a data-row-link href="{{ route('customers.show', $customer) }}" class="text-brand underline">
                            {{ $customer->name }}
                        </a>
                    </th>
                    <td class="whitespace-nowrap"><x-ui.contact :value="$customer->phone" kind="phone" /></td>
                    <td>{{ $customer->email ?? '—' }}</td>
                </tr>
            @empty
                <x-ui.empty :columns="3">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhum cliente cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$customers" />
@endsection
