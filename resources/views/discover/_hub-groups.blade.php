{{-- Discover hub cards, grouped (DiscoverController::hub). Styles: the .dh-* rules in discover/hub. --}}
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
