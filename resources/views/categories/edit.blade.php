@extends('layout')

@section('title', 'Editar categoria | Saler')

@section('content')
    <x-ui.page-header :title="'Editar categoria'" :back="route('categories.index')" back-label="Categorias" />

    <form method="POST" action="{{ route('categories.update', $category) }}"
        class="panel form-panel max-w-2xl">
        @csrf
        @method('PATCH')
        @include('categories.name-field', ['category' => $category])
        <div class="flex items-center gap-4">
            <button class="button-primary">Salvar</button>
            <a href="{{ route('categories.index') }}" class="button-secondary">Cancelar</a>
        </div>
    </form>
@endsection
