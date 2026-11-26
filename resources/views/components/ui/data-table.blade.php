@props(['id', 'title', 'label' => null, 'meta' => null, 'unit' => null, 'paginator' => null])

@php
    if ($meta === null && $unit !== null) {
        $count = $paginator?->total() ?? 0;
        $meta = $count.' '.($count === 1 ? $unit[0] : $unit[1]);
    }
@endphp

<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" {{ $attributes->class(['panel table-section']) }} data-live-table>
    <header class="table-section-header">
        <div class="min-w-0">
            <h2 id="{{ $id }}-title" class="table-section-title">{{ $title }}</h2>
            @if ($meta !== null)
                <p id="{{ $id }}-meta" class="table-section-meta" data-live-region>{{ $meta }}</p>
            @endif
        </div>
        @if (isset($filters) || isset($actions))
            <div class="table-section-tools">
                {{ $filters ?? '' }}
                {{ $actions ?? '' }}
            </div>
        @endif
    </header>
    <div id="{{ $id }}-results" class="table-results" data-live-region>
        <x-ui.table :label="$label ?? $title" :framed="false">{{ $slot }}</x-ui.table>
        @if ($paginator)
            <x-ui.pagination :paginator="$paginator" />
        @endif
    </div>
</section>
