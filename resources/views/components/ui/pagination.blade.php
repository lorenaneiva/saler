@props(['paginator'])

@if ($paginator->total() > 0)
    <div class="pagination">
        <p>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}</p>
        @if ($paginator->hasPages())
            <nav aria-label="Paginação">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true">Anterior</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif
                <span aria-current="page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>
                @else
                    <span aria-disabled="true">Próxima</span>
                @endif
            </nav>
        @endif
    </div>
@endif
