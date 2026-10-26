{{--
    "Browse profiles by" tabs (Oct 2026; Admin → Homepage Content → Garland &
    Harmony sections). Links come from the Discover pages that have members,
    biggest first; Community comes first (owner, 26 Oct 2026).
    Every link is in the HTML (search engines and no-JS visitors see them all);
    Alpine shows one tab at a time.
--}}
@php $browseTabs = \App\Support\HomeBrowse::tabs(); @endphp
@if(count($browseTabs))
<style>
    .home-garland .g-tabs { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin: 32px 0 26px; }
    @media (max-width: 639px) { .home-garland .g-tabs { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; padding-bottom: 6px; margin-left: -16px; margin-right: -16px; padding-left: 16px; padding-right: 16px; } }
    .home-garland .g-tab { flex: none; padding: 9px 18px; border-radius: 999px; border: 1px solid var(--g-line); background: #fff; color: var(--g-ink); font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background-color .15s, color .15s, border-color .15s; }
    .home-garland .g-tab:hover { border-color: var(--g-p); color: var(--g-p); }
    .home-garland .g-tab.on { background: var(--g-p); border-color: var(--g-p); color: #fff; }
    .home-garland .g-panel { max-width: 60rem; margin: 0 auto; text-align: center; }
    .home-garland .g-panel .g-chips { justify-content: center; }
    .home-garland .g-all { display: inline-block; margin-top: 22px; }
</style>
<section class="g-section g-lav" x-data="{ tab: @js(array_key_first($browseTabs)) }">
    <div class="g-wrap">
        <x-dynamic-component :component="$divider" />
        <h2 class="g-h2">Browse Profiles By</h2>
        <p class="g-lead">Find members by their community, where they live, their language or their profession.</p>

        <div class="g-tabs" role="tablist" aria-label="Browse profiles by">
            @foreach($browseTabs as $key => $tab)
                <button type="button" role="tab" id="g-tab-{{ $key }}" class="g-tab {{ $loop->first ? 'on' : '' }}"
                    :class="{ 'on': tab === @js($key) }" :aria-selected="tab === @js($key)" aria-controls="g-panel-{{ $key }}"
                    @click="tab = @js($key)">{{ $tab['label'] }}</button>
            @endforeach
        </div>

        @foreach($browseTabs as $key => $tab)
            <div class="g-panel" role="tabpanel" id="g-panel-{{ $key }}" aria-labelledby="g-tab-{{ $key }}" x-show="tab === @js($key)" @unless($loop->first) x-cloak @endunless>
                <div class="g-chips">
                    @foreach($tab['links'] as $link)
                        <a href="{{ $link['url'] }}" class="g-chip">{{ $link['label'] }}</a>
                    @endforeach
                </div>
                <a href="{{ $tab['all_url'] }}" class="g-link g-all">View all &rarr;</a>
            </div>
        @endforeach
    </div>
</section>
@endif
