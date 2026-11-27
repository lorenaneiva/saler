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

    <x-ui.data-table id="categories" title="Categorias cadastradas" :paginator="$categories" :unit="['categoria', 'categorias']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar categoria" placeholder="Nome da categoria" :active="request()->filled('q') || $status !== 'ativas'">
                <x-ui.table-filter label="Situação">
                    <select name="situacao" class="field">
                        <option value="ativas" @selected($status === 'ativas')>Ativa</option>
                        <option value="inativas" @selected($status === 'inativas')>Inativa</option>
                        <option value="todas" @selected($status === 'todas')>Todas</option>
                    </select>
                </x-ui.table-filter>
            </x-ui.list-search>
        </x-slot:filters>

        <thead>
            <tr>
                <x-ui.sort-header :sort="$sort" column="nome">Categoria</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="produtos" class="numeric">Produtos</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="situacao">Situação</x-ui.sort-header>
                <th scope="col">Ações</th>
            </tr>
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
                        <x-ui.badge :tone="$category->is_active ? 'success' : 'neutral'">{{ $category->is_active ? 'Ativa' : 'Inativa' }}</x-ui.badge>
                    </td>
                    <td>
                        <div class="row-actions">
                            <x-ui.inline-actions :id="$editForm" :action="route('categories.update', $category)" :label="'Editar '.$category->name" />
                            @if ($category->canBeDeleted())
                                <form data-inline-display method="POST" action="{{ route('categories.destroy', $category) }}"
                                    data-confirm="Excluir a categoria {{ $category->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button-quiet danger button-small" aria-label="Excluir {{ $category->name }}">Excluir</button>
                                </form>
                            @elseif ($category->is_active && $category->active_products_count > 0)
                                <button type="button" data-inline-display disabled class="button-quiet danger button-small"
                                    title="Desative os produtos ativos desta categoria antes"
                                    aria-label="Desativar {{ $category->name }} indisponível: há produtos ativos">Desativar</button>
                            @else
                                <form data-inline-display method="POST" action="{{ $category->is_active ? route('categories.deactivate', $category) : route('categories.activate', $category) }}"
                                    @if ($category->is_active) data-confirm="Desativar a categoria {{ $category->name }}?" @endif>
                                    @csrf
                                    @method('PATCH')
                                    <button @class(['button-quiet button-small', 'danger' => $category->is_active])
                                        aria-label="{{ $category->is_active ? 'Desativar' : 'Ativar' }} {{ $category->name }}">
                                        {{ $category->is_active ? 'Desativar' : 'Ativar' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="4">{{ $search !== '' ? 'Nenhuma categoria encontrada para esta busca.' : match ($status) { 'ativas' => 'Nenhuma categoria ativa.', 'inativas' => 'Nenhuma categoria inativa.', default => 'Nenhuma categoria cadastrada.' } }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>

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
