@extends('layout')

@section('title', 'Fornecedor | Saler')

@section('content')
    <div class="grid max-w-3xl gap-6">
        <h1 class="page-title">{{ $supplier->exists ? 'Editar fornecedor' : 'Novo fornecedor' }}</h1>

        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
            class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            @csrf
            @if ($supplier->exists)
                @method('PATCH')
            @endif

            @foreach ([
                'name' => ['Nome', true],
                'contact' => ['Contato', true],
                'representative' => ['Representante (opcional)', false],
            ] as $field => [$label, $required])
                @php($value = old($field, $supplier->{$field}))
                <div class="grid content-start gap-2">
                    <label for="{{ $field }}" class="font-medium">{{ $label }} @if ($required)<x-required-mark />@endif</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text" class="field" maxlength="255"
                        @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                    @error($field)
                        <p id="{{ $field }}-error" class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex gap-3 sm:col-span-2">
                <button class="button-primary">{{ $supplier->exists ? 'Salvar' : 'Cadastrar fornecedor' }}</button>
                <a href="{{ route('suppliers.index') }}"
                    class="button-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
