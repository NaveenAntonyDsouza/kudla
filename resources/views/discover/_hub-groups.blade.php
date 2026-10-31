{{-- Discover hub (DiscoverController::hub): every section a row of chips, each with its
     "All …" link — communities first, the same list as the homepage's Community tab.
     Styles: .dh-* in discover/hub. --}}
@foreach($chipRows as $row)
    <section class="dh-section" aria-labelledby="dh-chips-{{ $loop->index }}">
        <h2 id="dh-chips-{{ $loop->index }}">{{ $row['title'] }}</h2>
        <div class="dh-chips">
            @foreach($row['links'] as $link)
                <a href="{{ $link['url'] }}" class="dh-chip">{{ $link['label'] }} <span class="dh-chip-n">{{ number_format($link['count']) }}</span></a>
            @endforeach
            <a href="{{ route('discover.category', $row['all']) }}" class="dh-chip dh-chip-all">{{ $row['all_label'] }} →</a>
        </div>
    </section>
@endforeach
