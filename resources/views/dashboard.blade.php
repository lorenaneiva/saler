@extends('layout')

@section('title', 'Painel | Saler')

@section('content')
    <x-ui.page-header :title="'Olá, '.auth()->user()->name" description="Acesse as principais operações do Saler." />

    <section class="panel form-panel" aria-labelledby="quick-actions">
        <h2 id="quick-actions" class="text-base font-semibold">Ações rápidas</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sales.create') }}" class="button-primary">Nova venda</a>
            <a href="{{ route('purchases.create') }}" class="button-secondary">Nova compra</a>
            <a href="{{ route('customers.create') }}" class="button-secondary">Novo cliente</a>
            <a href="{{ route('products.create') }}" class="button-secondary">Novo produto</a>
        </div>
    </section>

    <div class="grid items-start gap-4 xl:grid-cols-2">
    @foreach ([
        'Operações' => [
            ['Vendas', 'Consulte as vendas registradas.', 'sales.index'],
            ['Compras', 'Consulte as entradas de mercadoria.', 'purchases.index'],
            ['Estoque', 'Acompanhe saldos, movimentações e perdas.', 'stock.index'],
        ],
        'Cadastros' => [
            ['Clientes', 'Acesse compras, saldos e pagamentos.', 'customers.index'],
            ['Produtos', 'Consulte preços e disponibilidade.', 'products.index'],
            ['Categorias', 'Organize os grupos de produtos.', 'categories.index'],
            ['Fornecedores', 'Consulte fornecedores e contatos.', 'suppliers.index'],
        ],
    ] as $group => $modules)
        <section class="grid gap-2">
            <h2 class="text-base font-semibold">{{ $group }}</h2>
            <ul class="panel grid divide-y divide-slate-200">
                @foreach ($modules as [$name, $description, $route])
                    <li>
                        <a href="{{ route($route) }}" class="flex min-h-14 items-center justify-between gap-3 px-4 py-2 transition-colors duration-200 hover:bg-blue-50">
                            <span>
                                <span class="block text-sm font-semibold">{{ $name }}</span>
                                <span class="block text-sm text-slate-600">{{ $description }}</span>
                            </span>
                            <span aria-hidden="true" class="text-brand">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
    </div>
@endsection
