<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Saler')</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-stone-100 text-stone-950">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('dashboard') }}" class="text-xl font-semibold">Saler</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg border border-stone-400 px-4 py-2 hover:bg-stone-100">Sair</button>
            </form>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-6 py-8">
        @yield('content')
    </main>
</body>
</html>
