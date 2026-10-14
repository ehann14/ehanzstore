@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-center items-center gap-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="px-4 py-2 bg-white/5 text-slate-600 rounded-lg text-sm cursor-not-allowed">Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 bg-white/5 border border-white/10 text-slate-300 rounded-lg text-sm hover:bg-electric hover:text-white hover:border-electric transition">Prev</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-4 py-2 bg-white/5 text-slate-500 rounded-lg text-sm">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-4 py-2 bg-electric text-white rounded-lg text-sm font-bold shadow-md shadow-electric/30">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-4 py-2 bg-white/5 border border-white/10 text-slate-300 rounded-lg text-sm hover:bg-electric hover:text-white hover:border-electric transition">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 bg-white/5 border border-white/10 text-slate-300 rounded-lg text-sm hover:bg-electric hover:text-white hover:border-electric transition">Next</a>
        @else
            <span class="px-4 py-2 bg-white/5 text-slate-600 rounded-lg text-sm cursor-not-allowed">Next</span>
        @endif
    </nav>
@endif