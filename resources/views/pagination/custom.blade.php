@if ($paginator->hasPages())
    <nav class="pagi" aria-label="Pagination">
        <ul>
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true"><span class="arr">‹</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><span class="arr">‹</span></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled"><span>{{ $element }}</span></li>
                @elseif (is_array($element))
                    @foreach ($element as $page)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><span class="arr">›</span></a></li>
            @else
                <li class="disabled" aria-disabled="true"><span class="arr">›</span></li>
            @endif
        </ul>
    </nav>
@endif
