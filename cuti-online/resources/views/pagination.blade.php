@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1 text-sm sm:justify-end" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="inline-flex h-9 items-center rounded-xl bg-white text-slate-400 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)]">
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
            </span>
        @else
            <a class="inline-flex h-9 items-center rounded-xl bg-white text-slate-700 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)] transition-colors duration-150 hover:bg-slate-50" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
            </a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if ($page === $paginator->currentPage())
                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-sky-600 px-3 text-sm font-semibold text-white shadow-[0_6px_16px_rgba(2,132,199,0.24)]" aria-current="page">{{ $page }}</span>
            @else
                <a class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-white px-3 text-sm font-medium text-slate-700 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)] transition-colors duration-150 hover:bg-slate-50" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="inline-flex h-9 items-center rounded-xl bg-white text-slate-700 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)] transition-colors duration-150 hover:bg-slate-50" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
            </a>
        @else
            <span class="inline-flex h-9 items-center rounded-xl bg-white text-slate-400 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)]">
                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
            </span>
        @endif
    </nav>
@endif