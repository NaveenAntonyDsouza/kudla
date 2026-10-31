{{-- Discover hub (DiscoverController::hub): rows of chips first — the communities, the same
     list as the homepage's Community tab, and on the Catholic sites the dioceses — then the
     cards, grouped. Styles: .dh-* in discover/hub. --}}
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

@foreach($groups as $group)
    <section class="dh-section" aria-labelledby="dh-group-{{ $loop->index }}">
        <h2 id="dh-group-{{ $loop->index }}">{{ $group['title'] }}</h2>
        <div class="dh-grid">
            @foreach($group['sections'] as $section)
                <a href="{{ route('discover.category', $section['slug']) }}" class="dh-card">
                    <span class="dh-head">
                        <span class="dh-name">{{ $section['hub_label'] }}</span>
                        <span class="dh-count">{{ number_format($section['count']) }} {{ \Illuminate\Support\Str::plural('profile', $section['count']) }}</span>
                    </span>
                    @if(! empty($section['top']))
                        <span class="dh-top">{{ implode(' · ', $section['top']) }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endforeach
