@props(['value', 'kind'])

@php
    $formatted = match ($kind) {
        'phone' => preg_replace('/^([0-9]{2})([0-9]{4,5})([0-9]{4})$/', '($1) $2-$3', $value ?? ''),
        'cpf' => preg_replace('/^([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})$/', '$1.$2.$3-$4', $value ?? ''),
        default => $value,
    };
@endphp
@if ($formatted)
{{ $formatted }}
@else
<x-ui.blank />
@endif
