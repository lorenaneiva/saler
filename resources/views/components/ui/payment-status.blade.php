@props(['status'])

@if ($status === '—')
    <span {{ $attributes }}>—</span>
@else
    <x-ui.badge :tone="match ($status) { 'Quitada' => 'success', 'Pendente' => 'danger', default => 'warning' }" {{ $attributes }}>{{ $status }}</x-ui.badge>
@endif
