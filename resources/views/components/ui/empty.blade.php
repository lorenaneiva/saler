@props(['columns' => null, 'title' => null])

@php
    $content = $title === null ? $slot : null;
@endphp

@if ($columns)
    <tr class="empty-row">
        <td colspan="{{ $columns }}" class="empty-state">
            @if ($title !== null)
                <p class="empty-state-title">{{ $title }}</p>
                @if ($slot->isNotEmpty())
                    <p class="mt-1">{{ $slot }}</p>
                @endif
            @else
                {{ $content }}
            @endif
        </td>
    </tr>
@else
    <div {{ $attributes->class(['empty-state']) }}>{{ $slot }}</div>
@endif
