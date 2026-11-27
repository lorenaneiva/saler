@props(['sort', 'column'])

@php
    $direction = $sort->directionOf($column);
    $nextLabel = $sort->nextDirection($column) === 'asc' ? 'Ordenar em ordem crescente' : 'Ordenar em ordem decrescente';
@endphp

<th scope="col" {{ $attributes->class(['sortable', 'is-sorted' => $direction !== null])->merge(['aria-sort' => match ($direction) { 'asc' => 'ascending', 'desc' => 'descending', default => null }]) }}>
    <a href="{{ $sort->url($column) }}" class="sort-link" title="{{ $nextLabel }}" data-sort-link="{{ $sort->parameter }}:{{ $column }}">
        <span>{{ $slot }}</span>
        <svg class="sort-icon" viewBox="0 0 12 16" fill="none" aria-hidden="true" @if ($direction) data-direction="{{ $direction }}" @endif>
            <path class="sort-icon-asc" d="m3 6.5 3-3 3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="sort-icon-desc" d="m3 9.5 3 3 3-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="sr-only">, {{ mb_strtolower($nextLabel) }}</span>
    </a>
</th>
