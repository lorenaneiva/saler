@props(['label', 'placeholder', 'name' => 'q', 'keep' => []])

@php
    $parameters = array_filter(request()->only($keep), 'is_string');
    $value = request()->query()[$name] ?? '';
    $value = is_string($value) ? mb_substr(trim($value), 0, 100) : '';
@endphp

<form method="GET" action="{{ url()->current() }}" role="search" aria-label="{{ $label }}"
    class="panel flex flex-wrap items-end gap-2 p-3">
    @foreach ($parameters as $key => $parameter)
        <input type="hidden" name="{{ $key }}" value="{{ $parameter }}">
    @endforeach
    <label class="grid w-full gap-1 text-sm font-medium sm:w-80">
        {{ $label }}
        <input type="search" name="{{ $name }}" value="{{ $value }}" maxlength="100" placeholder="{{ $placeholder }}" class="field">
    </label>
    {{ $slot }}
    <button class="button-secondary">Buscar</button>
    @if ($value !== '')
        <a class="button-quiet" href="{{ route(request()->route()->getName(), array_merge(request()->route()->parameters(), $parameters)) }}">Limpar</a>
    @endif
</form>
