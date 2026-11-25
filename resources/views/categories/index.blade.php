@extends('layout')

@section('title', 'Categorias | Saler')

@section('content')
    <div class="grid max-w-2xl gap-6">
        <h1 class="text-2xl font-semibold">Categorias</h1>

        @if (session('status'))
            <p role="status" class="rounded-lg bg-green-100 px-4 py-3 text-green-900">{{ session('status') }}</p>
        @endif

        @error('category')
            <p role="alert" class="rounded-lg bg-red-100 px-4 py-3 text-red-900">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ route('categories.store') }}" class="grid gap-3 rounded-lg border border-stone-200 bg-white p-4">
            @csrf
            @include('categories.name-field')
            <button class="justify-self-start rounded-lg bg-stone-900 px-4 py-2 text-white hover:bg-stone-700">Cadastrar categoria</button>
        </form>

        <ul class="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-white">
            @forelse ($categories as $category)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <span class="min-w-0 break-words">{{ $category->name }}</span>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('categories.edit', $category) }}" class="underline"
                            aria-label="Editar {{ $category->name }}">Editar</a>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-800 underline" aria-label="Excluir {{ $category->name }}">Excluir</button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-4 py-3 text-stone-600">Nenhuma categoria cadastrada.</li>
            @endforelse
        </ul>

        {{ $categories->links() }}
    </div>
@endsection
