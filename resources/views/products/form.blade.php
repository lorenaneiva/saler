@extends('layout')

@section('title', 'Produto | Saler')

@section('content')
    <x-ui.page-header :title="$product->exists ? 'Editar produto' : 'Novo produto'" :back="route('products.index')" back-label="Produtos" />

    <div class="panel max-w-2xl">
        @include('products.editor')
    </div>
@endsection
