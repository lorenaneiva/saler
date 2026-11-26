@props(['label', 'placeholder', 'name' => 'q', 'keep' => [], 'active' => null, 'disabled' => false])

@php
    $parameters = array_filter(request()->only($keep), 'is_string');
    $value = request()->query()[$name] ?? '';
    $value = is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    $clearUrl = route(request()->route()->getName(), array_merge(request()->route()->parameters(), $parameters));
@endphp

<form id="{{ $name }}-search" method="GET" action="{{ url()->current() }}" role="search" aria-label="{{ $label }}" class="table-search" data-live-search>
    <label class="table-search-field">
        <span class="sr-only">{{ $label }}</span>
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="9" cy="9" r="5.25" stroke="currentColor" stroke-width="1.5"/><path d="m13 13 3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        <input type="search" name="{{ $name }}" value="{{ $value }}" maxlength="100" placeholder="{{ $placeholder }}" autocomplete="off" class="field" @disabled($disabled) {{ $attributes->only(['autocapitalize', 'spellcheck']) }}>
    </label>
    {{ $slot }}
    <span id="{{ $name }}-search-controls" class="contents" data-live-region>
        @foreach ($parameters as $key => $parameter)
            <input type="hidden" name="{{ $key }}" value="{{ $parameter }}">
        @endforeach
        <x-ui.clear-filters :href="$clearUrl" :active="! $disabled && ($active ?? $value !== '')" />
    </span>
</form>
