@extends('layout')

@section('title', 'Editar categoria | Saler')

@section('content')
    <div class="grid max-w-2xl gap-6">
        <h1 class="page-title">Editar categoria</h1>

        <form method="POST" action="{{ route('categories.update', $category) }}"
            class="panel grid gap-3 p-5">
            @csrf
            @method('PATCH')
            @include('categories.name-field', ['category' => $category])
            <div class="flex items-center gap-4">
                <button class="button-primary">Salvar</button>
                <a href="{{ route('categories.index') }}" class="underline">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
