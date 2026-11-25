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
                ['dashboard', 'dashboard', 'Painel', '▦'],
                ['sales.index', 'sales.*', 'Vendas', '↗'],
                ['purchases.index', 'purchases.*', 'Compras', '↙'],
                ['stock.index', 'stock.*', 'Estoque', '▤'],
            ],
            'Cadastros' => [
                ['customers.index', 'customers.*', 'Clientes', '◉'],
                ['products.index', 'products.*', 'Produtos', '◇'],
                ['categories.index', 'categories.*', 'Categorias', '▥'],
                ['suppliers.index', 'suppliers.*', 'Fornecedores', '▣'],
            ],
        ];
    @endphp
    <aside class="app-sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span aria-hidden="true" class="brand-mark">S</span>
            <span>Saler</span>
        </a>

        <details class="mobile-navigation">
            <summary>Menu <span aria-hidden="true">☰</span></summary>
            <nav aria-label="Menu principal" class="module-nav">
                @include('partials.module-links', ['groups' => $groups])
            </nav>
        </details>
        <nav aria-label="Menu principal" class="module-nav desktop-navigation">
            @include('partials.module-links', ['groups' => $groups])
        </nav>
    </aside>
    <div class="min-w-0">
        <header class="app-topbar">
            <div class="workspace topbar-inner">
                <span class="topbar-account">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button-secondary button-small">Sair</button>
                </form>
            </div>
        </header>
        <main id="conteudo" class="workspace app-content" tabindex="-1">
            @if (session('status'))
                <x-ui.alert>{{ session('status') }}</x-ui.alert>
            @endif
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
