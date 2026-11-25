@extends('layout')

@section('content')
    <h1 class="text-2xl font-semibold">Olá, {{ auth()->user()->name }}</h1>
    <a href="{{ route('categories.index') }}" class="mt-4 inline-block underline">Categorias</a>
@endsection
