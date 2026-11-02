{{-- Minimal pagination that matches the admin card styling. --}}
@if ($paginator->hasPages())
    <nav class="admin-pagination" aria-label="Navigasi halaman">
        <span>Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>

        <div class="admin-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-hidden="true">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page === $paginator->currentPage())
                    <span class="is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span class="is-disabled" aria-hidden="true">›</span>
            @endif
        </div>
    </nav>
@endif
