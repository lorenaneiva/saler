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

    <x-ui.list-search label="Buscar categoria" placeholder="Nome da categoria" />

    <x-ui.table label="Categorias cadastradas">
        <thead>
            <tr><th scope="col">Categoria</th><th scope="col" class="numeric">Produtos</th><th scope="col" class="text-right">Ações</th></tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                @php($editForm = 'category-'.$category->id)
                <tr data-inline-editor>
                    <th scope="row" class="wrap-anywhere font-semibold">
                        <x-ui.inline-field :form="$editForm" name="name" label="Categoria" :value="$category->name" required maxlength="255" />
                    </th>
                    <td class="numeric">
                        @if ($category->products_count)
                            <a href="{{ route('products.index', ['categoria' => $category->id]) }}">{{ $category->products_count }}</a>
                        @else
                            0
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            <x-ui.inline-actions :id="$editForm" :action="route('categories.update', $category)" :label="'Editar '.$category->name" />
                            @if ($category->products_count === 0)
                                <form data-inline-display method="POST" action="{{ route('categories.destroy', $category) }}"
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
