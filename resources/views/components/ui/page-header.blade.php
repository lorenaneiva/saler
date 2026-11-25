@props(['title', 'description' => null, 'back' => null, 'backLabel' => 'Voltar'])

<header {{ $attributes->class(['page-header']) }}>
    <div class="min-w-0">
        @if ($back)
            <a class="back-link" href="{{ $back }}">{{ $backLabel }}</a>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if ($description)
            <p class="page-description">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</header>
