@props(['product'])

@php($margin = $product->profitMargin())
{{ $margin === null ? '—' : number_format((float) $margin, 2, ',', '.').'%' }}
