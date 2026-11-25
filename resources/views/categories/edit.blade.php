@extends('layout')

@section('title', 'Editar categoria | Saler')

@section('content')
    <div class="grid max-w-2xl gap-6">
        <h1 class="text-2xl font-semibold">Editar categoria</h1>

        <form method="POST" action="{{ route('categories.update', $category) }}"
            class="grid gap-3 rounded-lg border border-stone-200 bg-white p-4">
            @csrf
            @method('PATCH')
            @include('categories.name-field', ['category' => $category])
            <div class="flex items-center gap-4">
                <button class="rounded-lg bg-stone-900 px-4 py-2 text-white hover:bg-stone-700">Salvar</button>
                <a href="{{ route('categories.index') }}" class="underline">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
