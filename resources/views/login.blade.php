<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar no Saler</title>
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-screen place-items-center bg-stone-100 p-6 text-stone-950">
    <main class="w-full max-w-sm rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-semibold">Entrar no Saler</h1>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 grid gap-5">
            @csrf
            <div class="grid gap-2">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required class="rounded-lg border border-stone-400 p-2">
                @error('email') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="rounded-lg border border-stone-400 p-2">
                @error('password') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <button class="rounded-lg bg-emerald-800 px-4 py-2 font-medium text-white hover:bg-emerald-900">Entrar</button>
        </form>
    </main>
</body>
</html>
