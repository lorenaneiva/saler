<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar no Saler</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-shell">
    <main class="panel login-panel">
        <span class="brand-mark" aria-hidden="true">S</span>
        <div>
            <h1 class="page-title">Entrar no Saler</h1>
            <p class="page-description">Entre com sua conta para acessar o sistema.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="grid gap-4">
            @csrf
            <div class="grid gap-1.5">
                <label for="email" class="text-sm font-medium">E-mail <x-required-mark /></label>
                <input id="email" name="email" type="email" autocomplete="username"
                    value="{{ is_string(old('email')) ? old('email') : '' }}" required class="field"
                    @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
                @error('email') <p id="login-email-error" role="alert" class="text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-1.5">
                <label for="password" class="text-sm font-medium">Senha <x-required-mark /></label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="field"
                    @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
                @error('password') <p id="login-password-error" role="alert" class="text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <button class="button-primary !w-full">Entrar</button>
        </form>
    </main>
</body>
</html>
