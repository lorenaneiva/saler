@props(['name'])

<svg class="module-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <use href="{{ asset('material-icons.svg') }}#{{ $name }}"></use>
</svg>
