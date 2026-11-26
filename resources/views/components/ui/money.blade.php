@props(['value'])

@php
    $amount = is_numeric($value) ? (float) $value : 0.0;
@endphp
R$ {{ number_format($amount, 2, ',', '.') }}
