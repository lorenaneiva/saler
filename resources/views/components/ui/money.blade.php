@props(['value'])

@php
    $numericValue = $value instanceof Stringable ? (string) $value : $value;
    $amount = is_numeric($numericValue) ? (float) $numericValue : 0.0;
@endphp
R$ {{ number_format($amount, 2, ',', '.') }}
