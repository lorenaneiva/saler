@extends('layout')

@section('content')
    <h1 class="page-title">Olá, {{ auth()->user()->name }}</h1>
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('categories.index') }}" class="panel block p-6 hover:border-emerald-700">
            <span class="text-lg font-semibold">Categorias</span>
            <p class="mt-1 text-sm text-slate-600">Organize os grupos de produtos.</p>
        </a>
    </div>
@endsection
