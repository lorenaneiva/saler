@props(['paginator'])

@php
    $pageName = $paginator->getPageName();
    $currentPage = $paginator->currentPage();
    $lastPage = max(1, $paginator->lastPage());
    $parameters = array_filter(request()->except($pageName), 'is_string');
@endphp

<nav class="table-pagination" aria-label="Paginação">
    <p class="table-pagination-range">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}</p>
    <div class="table-pagination-controls">
        @if ($paginator->onFirstPage())
            <span class="pagination-step" aria-disabled="true" aria-label="Página anterior"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12 5-5 5 5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        @else
            <a class="pagination-step" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12 5-5 5 5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        @endif
        <form method="GET" action="{{ url()->current() }}" class="pagination-jump" data-pagination-jump>
            @foreach ($parameters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label>
                <span class="sr-only">Página atual, de {{ $lastPage }}</span>
                <input type="number" name="{{ $pageName }}" value="{{ $currentPage }}" min="1" max="{{ $lastPage }}" step="1" inputmode="numeric"
                    class="field pagination-input" data-current-page="{{ $currentPage }}" @disabled($lastPage === 1)>
            </label>
            <span aria-hidden="true">de {{ $lastPage }}</span>
        </form>
        @if ($paginator->hasMorePages())
            <a class="pagination-step" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Próxima página"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        @else
            <span class="pagination-step" aria-disabled="true" aria-label="Próxima página"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        @endif
    </div>
</nav>
