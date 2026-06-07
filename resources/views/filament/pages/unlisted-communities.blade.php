<x-filament-panels::page>
    @php
        $sections = [
            ['Caste / Community', $this->getUnlistedCastes(),        'Add to Content Management → Communities (as a Community).'],
            ['Sub-Caste',         $this->getUnlistedSubCastes(),     'Add as a sub-community under the right caste in Content Management → Communities.'],
            ['Religion',          $this->getUnlistedReligions(),     'Add to Content Management → Reference Data (religion list) if it should be offered.'],
            ['Denomination',      $this->getUnlistedDenominations(), 'Add to Content Management → Reference Data (denomination list).'],
        ];
    @endphp

    <p style="font-size:.875rem;opacity:.75;margin-bottom:.25rem;">
        Members who typed an "Other / Not Listed" value. Use this to grow your managed dropdowns:
        add the value in the place noted, then re-categorise those members
        (the <strong>"Unlisted caste/religion"</strong> filter on All Members lists them).
    </p>

    @foreach($sections as [$label, $groups, $hint])
        <x-filament::section :heading="$label . ' (' . $groups->count() . ')'" :description="$hint" collapsible :collapsed="$groups->isEmpty()">
            @forelse($groups as $g)
                <div style="display:flex;align-items:flex-start;gap:1rem;padding:.6rem 0;{{ $loop->first ? '' : 'border-top:1px solid rgba(128,128,128,0.18);' }}">
                    <div style="flex:1;min-width:0;">
                        <span style="font-weight:600;">{{ $g->value }}</span>
                        <span style="opacity:.6;margin-left:.4rem;font-size:.8125rem;">— {{ $g->count }} member{{ $g->count === 1 ? '' : 's' }}</span>
                        <div style="margin-top:.3rem;display:flex;flex-wrap:wrap;gap:.3rem;">
                            @foreach($g->members as $m)
                                @php $url = \App\Filament\Resources\UserResource::profileViewUrl($m); @endphp
                                @if($url)
                                    <a href="{{ $url }}" title="{{ $m->full_name }}" style="font-size:.75rem;color:#8B1D91;text-decoration:none;border:1px solid rgba(139,29,145,.3);border-radius:9999px;padding:.05rem .55rem;">{{ $m->matri_id }}</a>
                                @else
                                    <span style="font-size:.75rem;opacity:.6;">{{ $m->matri_id }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <p style="opacity:.6;font-size:.875rem;">No unlisted {{ strtolower($label) }} entries — your dropdowns cover everyone here.</p>
            @endforelse
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
