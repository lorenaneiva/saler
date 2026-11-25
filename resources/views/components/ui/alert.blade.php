@props(['tone' => 'success'])

<div role="{{ $tone === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['notice', 'notice-'.$tone]) }}>
    {{ $slot }}
</div>
