@props(['columns' => null])

@if ($columns)
    <tr><td colspan="{{ $columns }}" class="empty-state">{{ $slot }}</td></tr>
@else
    <div {{ $attributes->class(['empty-state']) }}>{{ $slot }}</div>
@endif
