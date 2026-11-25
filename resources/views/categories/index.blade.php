@extends('layout')

@section('title', 'Categorias | Saler')

@section('content')
    <x-ui.page-header title="Categorias" :description="$registeredCount.' no cadastro de produtos'">
        <x-slot:actions>
            <button type="button" class="button-primary" data-dialog-open="new-category"><span aria-hidden="true">＋</span> Nova categoria</button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('category')
        <x-ui.alert tone="error">{{ $message }}</x-ui.alert>
    @enderror

    @if ($registeredCount > 0 || $search !== '')
        <form method="GET" action="{{ route('categories.index') }}" class="panel flex flex-wrap items-end gap-2 p-3" role="search">
            <label class="grid w-full gap-1 text-sm font-medium sm:w-72">
                Buscar categoria
                <input class="field" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Nome da categoria">
            </label>
            <button class="button-secondary">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('categories.index') }}" class="button-quiet">Limpar</a>
            @endif
        </form>
    @endif

    <x-ui.table label="Categorias cadastradas">
        <thead>
            <tr><th scope="col">Categoria</th><th scope="col" class="numeric">Produtos</th><th scope="col" class="text-right">Ações</th></tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <th scope="row" class="wrap-anywhere font-semibold">{{ $category->name }}</th>
                    <td class="numeric">
                        @if ($category->products_count)
                            <a href="{{ route('products.index', ['categoria' => $category->id]) }}">{{ $category->products_count }}</a>
                        @else
                            0
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('categories.edit', $category) }}" class="button-quiet button-small"
                                aria-label="Editar {{ $category->name }}">Editar</a>
                            @if ($category->products_count === 0)
                                <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                    data-confirm="Excluir a categoria {{ $category->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button-quiet danger button-small" aria-label="Excluir {{ $category->name }}">Excluir</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-600">Em uso</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="3">{{ $search === '' ? 'Nenhuma categoria cadastrada.' : 'Nenhuma categoria encontrada para esta busca.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$categories" />

    <x-ui.dialog id="new-category" title="Nova categoria" :auto-open="$errors->has('name')" inline-fallback>
        <form method="POST" action="{{ route('categories.store') }}" class="grid gap-2">
            @csrf
            @include('categories.name-field')
            <div class="form-actions">
                <button class="button-primary">Cadastrar categoria</button>
                <button type="button" class="button-secondary" data-dialog-close>Cancelar</button>
            </div>
        </form>
    </x-ui.dialog>
@endsection
