@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span></span>
        @else
            <a class="btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif

        <span class="small muted">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span></span>
        @endif
    </nav>
@endif
