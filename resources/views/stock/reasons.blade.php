@extends('layout')

@section('title', 'Motivos de ajuste | Saler')

@section('content')
    <div class="grid max-w-2xl gap-6">
        <div class="grid gap-3">
            <a href="{{ route('stock.index') }}" class="w-fit text-sm text-emerald-800 underline">Voltar ao estoque</a>
            <h1 class="page-title">Motivos de ajuste</h1>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('stock.reasons.store') }}" class="panel grid gap-3 p-5">
            @csrf
            @php($reasonName = old('name'))
            <label for="reason-name" class="font-medium">Nome do motivo <x-required-mark /></label>
            <input id="reason-name" name="name" class="field" required maxlength="100"
                value="{{ is_string($reasonName) ? $reasonName : '' }}"
                @error('name') aria-invalid="true" aria-describedby="reason-name-error" @enderror>
            @error('name')
                <p id="reason-name-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
            <button class="button-primary">Cadastrar motivo</button>
        </form>

        <ul class="panel divide-y divide-slate-200">
            @forelse ($reasons as $reason)
                <li class="wrap-anywhere px-4 py-3">{{ $reason->name }}</li>
            @empty
                <li class="px-4 py-3 text-slate-600">Nenhum motivo cadastrado.</li>
            @endforelse
        </ul>

        {{ $reasons->links() }}
    </div>
@endsection
