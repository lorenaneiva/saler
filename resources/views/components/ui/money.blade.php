@props(['value'])

@php
    [$integer, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');
    $integer = preg_replace('/\B(?=(?:[0-9]{3})+(?![0-9]))/', '.', $integer);
@endphp
R$ {{ $integer }},{{ str_pad($fraction, 2, '0') }}
