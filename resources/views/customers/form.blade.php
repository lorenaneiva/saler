@extends('layout')

@section('title', 'Cliente | Saler')

@section('content')
    <x-ui.page-header :title="$customer->exists ? 'Editar cliente' : 'Novo cliente'" :back="route('customers.index')" back-label="Clientes" />

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
        class="panel form-panel form-grid">
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
                <label for="{{ $field }}" class="font-medium">{{ $label }} @if ($required)<x-required-mark />@endif</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="field"
                    maxlength="{{ $maxLength }}" autocomplete="{{ $autocomplete }}"
                    @if (in_array($field, ['phone', 'cpf'], true)) data-mask="{{ $field }}" @endif
                    @if (in_array($field, ['cpf', 'postal_code'], true)) inputmode="numeric" @endif
                    @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @error($field)
                    <p id="{{ $field }}-error" class="text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="flex gap-3 sm:col-span-full">
            <button class="button-primary">{{ $customer->exists ? 'Salvar' : 'Cadastrar cliente' }}</button>
            <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}"
                class="button-secondary">Cancelar</a>
        </div>
    </form>
@endsection
