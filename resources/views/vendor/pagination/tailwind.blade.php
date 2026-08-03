@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between">
        <div class="text-sm text-slate-500">
            <span class="font-medium text-slate-700">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-medium text-slate-700">{{ $paginator->lastItem() }}</span>
            /
            <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
            kayıt
        </div>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-400">Önceki</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Önceki</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">Sonraki</a>
            @else
                <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-400">Sonraki</span>
            @endif
        </div>
    </nav>
@endif
