@props(['status'])

@if ($status === '—')
    <x-ui.blank {{ $attributes }} />
@else
    <x-ui.badge :tone="match ($status) { 'Quitada' => 'success', 'Pendente' => 'danger', default => 'warning' }" {{ $attributes }}>{{ $status }}</x-ui.badge>
@endif
