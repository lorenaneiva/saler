@props(['product'])

@if ($product->hasRegisteredCost())
    <x-ui.money :value="$product->average_cost" />
@else
    <span {{ $attributes->class(['text-unknown']) }}>Sem custo registrado</span>
@endif
