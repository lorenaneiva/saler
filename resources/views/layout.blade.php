<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Saler')</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
    <aside class="flex flex-col gap-5 bg-slate-950 px-5 py-5 text-slate-300 lg:sticky lg:top-0 lg:h-screen lg:gap-10 lg:p-6">
        <a href="{{ route('dashboard') }}" class="flex w-fit items-center gap-3 text-xl font-semibold text-white">
            <span aria-hidden="true" class="grid size-9 place-items-center rounded-xl bg-emerald-400 text-slate-950">S</span>
            Saler
        </a>
        <nav aria-label="Módulos" class="flex flex-wrap gap-2 lg:flex-col">
            <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
                class="module-link">Painel</a>
            <a href="{{ route('categories.index') }}" @if (request()->routeIs('categories.*')) aria-current="page" @endif
                class="module-link">Categorias</a>
            <a href="{{ route('products.index') }}" @if (request()->routeIs('products.*')) aria-current="page" @endif
                class="module-link">Produtos</a>
            <a href="{{ route('customers.index') }}" @if (request()->routeIs('customers.*')) aria-current="page" @endif
                class="module-link">Clientes</a>
        </nav>
    </aside>
    <div class="min-w-0">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-end gap-4 px-5 py-3 sm:px-8">
                <span class="min-w-0 truncate text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button-secondary">Sair</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:py-12">
            @yield('content')
        </main>
    </div>
</body>
</html>
