@extends('layout')

@section('title', 'Alterar senha | Saler')

@section('content')
    <x-ui.page-header title="Alterar senha" description="Confirme a senha atual e escolha uma nova." />

    @if ($mustChange)
        <x-ui.alert tone="info">Sua senha foi definida por outra pessoa. Crie uma nova senha para continuar usando o sistema.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="panel form-panel max-w-xl">
        @csrf
        @method('PUT')
        @foreach ([
            'current_password' => ['Senha atual', 'current-password', null],
            'password' => ['Nova senha', 'new-password', 'Mínimo de '.\App\Http\Requests\PasswordRules::MinimumLength.' caracteres, diferente da atual.'],
            'password_confirmation' => ['Confirmação da nova senha', 'new-password', null],
        ] as $field => [$label, $autocomplete, $hint])
            <div class="grid content-start gap-2">
                <label for="{{ $field }}" class="font-medium">{{ $label }} <x-required-mark /></label>
                <input id="{{ $field }}" name="{{ $field }}" type="password" class="field" required maxlength="255" autocomplete="{{ $autocomplete }}"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @elseif ($hint) aria-describedby="{{ $field }}-hint" @enderror>
                @error($field)
                    <p id="{{ $field }}-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
                @else
                    @if ($hint)
                        <p id="{{ $field }}-hint" class="text-xs text-slate-600">{{ $hint }}</p>
                    @endif
                @enderror
            </div>
        @endforeach
        <div class="flex gap-3">
            <button class="button-primary">Alterar senha</button>
            @unless ($mustChange)
                <a href="{{ route('dashboard') }}" class="button-secondary">Cancelar</a>
            @endunless
        </div>
    </form>
@endsection
