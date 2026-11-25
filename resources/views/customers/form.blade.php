@extends('layout')

@section('title', 'Cliente | Saler')

@section('content')
    <div class="grid max-w-3xl gap-6">
        <h1 class="page-title">{{ $customer->exists ? 'Editar cliente' : 'Novo cliente' }}</h1>

        <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
            class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            @csrf
            @if ($customer->exists)
                @method('PATCH')
            @endif

            @foreach ([
                'name' => ['Nome', 'text', 255, 'name', true],
                'phone' => ['Telefone', 'tel', 30, 'tel', true],
                'cpf' => ['CPF (opcional)', 'text', 14, 'off', false],
                'email' => ['E-mail (opcional)', 'email', 255, 'email', false],
                'postal_code' => ['CEP (opcional)', 'text', 9, 'postal-code', false],
            ] as $field => [$label, $type, $maxLength, $autocomplete, $required])
                @php($value = old($field, $customer->{$field}))
                <div class="grid content-start gap-2">
                    <label for="{{ $field }}" class="font-medium">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="field"
                        maxlength="{{ $maxLength }}" autocomplete="{{ $autocomplete }}"
                        @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                    @error($field)
                        <p id="{{ $field }}-error" class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex gap-3 sm:col-span-2">
                <button class="button-primary">{{ $customer->exists ? 'Salvar' : 'Cadastrar cliente' }}</button>
                <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}"
                    class="button-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
