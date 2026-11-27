@props(['product'])

@php($margin = $product->profitMargin())
@if ($margin === null)
<x-ui.blank />
@else
{{ number_format((float) $margin, 2, ',', '.') }}%
@endif
