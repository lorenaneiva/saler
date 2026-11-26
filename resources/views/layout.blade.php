<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Saler')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <noscript><style>dialog[data-inline-fallback]{display:block;position:static;max-width:none;margin:0} [data-dialog-close],[data-dialog-open]{display:none!important}</style></noscript>
</head>
<body class="app-shell">
    <a href="#conteudo" class="skip-link">Ir para o conteúdo</a>
    @php
        $groups = [
            'Operações' => [
                ['dashboard', 'dashboard', 'Painel', 'dashboard'],
                ['sales.index', 'sales.*', 'Vendas', 'point_of_sale'],
                ['purchases.index', 'purchases.*', 'Compras', 'shopping_cart'],
                ['stock.index', 'stock.*', 'Estoque', 'inventory_2'],
            ],
            'Cadastros' => [
                ['customers.index', 'customers.*', 'Clientes', 'people'],
                ['products.index', 'products.*', 'Produtos', 'local_offer'],
                ['categories.index', 'categories.*', 'Categorias', 'category'],
                ['suppliers.index', 'suppliers.*', 'Fornecedores', 'local_shipping'],
            ],
        ];
    @endphp
    <aside class="app-sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span aria-hidden="true" class="brand-mark">S</span>
            <span>Saler</span>
        </a>

        <details class="mobile-navigation">
            <summary>Menu <x-ui.module-icon name="menu" /></summary>
            <nav aria-label="Menu principal" class="module-nav">
                @include('partials.module-links', ['groups' => $groups])
                @include('partials.logout')
            </nav>
        </details>
        <nav aria-label="Menu principal" class="module-nav desktop-navigation">
            @include('partials.module-links', ['groups' => $groups])
        </nav>
        <div class="mt-auto hidden lg:block">
            @include('partials.logout')
        </div>
    </aside>
    <div class="min-w-0">
        <main id="conteudo" class="workspace app-content" tabindex="-1">
            <div id="flash-status" class="contents" data-live-region>
                @if (session('status'))
                    <x-ui.alert>{{ session('status') }}</x-ui.alert>
                @endif
            </div>
            @yield('content')
        </main>
    </div>
    <x-ui.dialog id="confirm-operation" title="Confirmar operação">
        <p data-confirm-message class="text-sm text-slate-700"></p>
        <div class="form-actions">
            <button type="button" class="button-secondary" data-dialog-close autofocus>Cancelar</button>
            <button type="button" class="button-danger" data-confirm-submit>Confirmar</button>
        </div>
    </x-ui.dialog>
</body>
</html>
