{{-- Discover hub (DiscoverController::hub): the communities as chips — the same list as
     the homepage's Community tab — then the cards, grouped. Styles: .dh-* in discover/hub. --}}
@if($communities->isNotEmpty())
    <section class="dh-section" aria-labelledby="dh-communities">
        <h2 id="dh-communities">Communities</h2>
        <div class="dh-chips">
            @foreach($communities as $community)
                <a href="{{ $community['url'] }}" class="dh-chip">{{ $community['label'] }} <span class="dh-chip-n">{{ number_format($community['count']) }}</span></a>
            @endforeach
            <a href="{{ route('discover.category', 'community-matrimony') }}" class="dh-chip dh-chip-all">All communities →</a>
        </div>
    </section>
@endif

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
