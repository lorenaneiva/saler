@props(['id', 'title', 'drawer' => false, 'autoOpen' => false, 'inlineFallback' => false])

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title"
    @class(['app-dialog', 'app-drawer' => $drawer])
    @if ($autoOpen) data-auto-open @endif @if ($inlineFallback) data-inline-fallback @endif>
    <div class="dialog-content">
        <header class="dialog-header">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button class="icon-button" type="button" data-dialog-close aria-label="Fechar">×</button>
        </header>
        <div class="dialog-body">{{ $slot }}</div>
    </div>
</dialog>
