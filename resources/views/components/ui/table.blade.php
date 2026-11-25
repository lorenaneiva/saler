@props(['label'])

<div {{ $attributes->class(['panel table-region']) }} role="region" aria-label="{{ $label }}" tabindex="0">
    <table class="data-table">
        <caption class="sr-only">{{ $label }}</caption>
        {{ $slot }}
    </table>
</div>
