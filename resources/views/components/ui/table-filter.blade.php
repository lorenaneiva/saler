@props(['label'])

<label {{ $attributes->class(['table-filter']) }}>
    <span class="table-filter-label">{{ $label }}</span>
    {{ $slot }}
</label>
