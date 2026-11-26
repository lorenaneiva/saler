@props(['id', 'title', 'meta' => null])

<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" {{ $attributes->class(['panel table-section']) }}>
    <header class="table-section-header">
        <div class="min-w-0">
            <h2 id="{{ $id }}-title" class="table-section-title">{{ $title }}</h2>
            @if ($meta !== null)
                <p id="{{ $id }}-meta" class="table-section-meta" data-live-region>{{ $meta }}</p>
            @endif
        </div>
        @if (isset($search) || isset($actions))
            <div class="table-section-tools">
                {{ $search ?? '' }}
                {{ $actions ?? '' }}
            </div>
        @endif
    </header>
    {{ $slot }}
</section>
