@props(['href', 'active' => false])

@if ($active)
    <a href="{{ $href }}" {{ $attributes->class('button-quiet clear-filters') }}>Limpar</a>
@else
    <span {{ $attributes->class('button-quiet clear-filters') }} aria-disabled="true" title="Nenhum filtro aplicado">Limpar</span>
@endif
