@props(['prefix', 'label', 'district' => true])
@php
    $cc = $prefix.'_country';
    $sc = $prefix.'_state';
    $dc = $prefix.'_district';
    $raw = (array) request($cc, []);
    $any = in_array('Any', $raw, true);
    $sel = array_values(array_filter($raw, fn($c) => $c !== '' && $c !== 'Any'));
@endphp
{{-- Country as a MULTI-select. State (and India District) only appear when
     exactly ONE country is chosen, since the cascade is per-country. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5"
    x-data="{
        countries: {{ Js::from($sel) }},
        any: {{ $any ? 'true' : 'false' }},
        open: false,
        search: '',
        s: '{{ request($sc, '') }}',
        d: '{{ request($dc, '') }}',
        states: [],
        districts: [],
        get single() { return (!this.any && this.countries.length === 1) ? this.countries[0] : ''; },
        get display() { if (this.any) return 'Any'; if (this.countries.length === 0) return ''; return this.countries.length + ' selected'; },
        matches(item) { return !this.search || item.toLowerCase().includes(this.search.toLowerCase()); },
        toggleAny() { this.any = !this.any; if (this.any) this.countries = []; this.afterCountryChange(); },
        toggle(v) {
            this.any = false;
            if (this.countries.includes(v)) this.countries = this.countries.filter(c => c !== v);
            else this.countries.push(v);
            this.afterCountryChange();
        },
        remove(v) { this.countries = this.countries.filter(c => c !== v); this.afterCountryChange(); },
        afterCountryChange() {
            this.s = ''; this.d = ''; this.states = []; this.districts = [];
            if (this.single) this.fetchStates();
        },
        async fetchStates() {
            const c = this.single;
            if (!c) { this.states = []; this.districts = []; return; }
            if (c === 'India') {
                this.states = await (await fetch('/api/cascade/states')).json();
            } else {
                const r = await (await fetch(`/api/cascade/countries?country=${encodeURIComponent(c)}`)).json();
                this.states = r.locations || [];
            }
            if (this.s) this.fetchDistricts();
        },
        async fetchDistricts() {
            if (!this.s || this.single !== 'India') { this.districts = []; return; }
            this.districts = await (await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.s)}`)).json();
        },
        init() { if (this.single) this.fetchStates(); }
    }">
    {{-- Country (multi-select) --}}
    <div class="relative" @click.away="open = false">
        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $label }} Country</label>
        <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-left bg-white hover:border-gray-400 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) transition-colors">
            <span :class="(any || countries.length) ? 'text-gray-900' : 'text-gray-400'" x-text="display || 'Any'"></span>
            <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        {{-- Selected tags --}}
        <div x-show="countries.length > 0 && !any" class="flex flex-wrap gap-1.5 mt-2">
            <template x-for="v in countries" :key="v">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-(--color-primary-light) text-(--color-primary)">
                    <span x-text="v" class="max-w-[120px] truncate"></span>
                    <button type="button" @click.stop="remove(v)" class="hover:text-red-600">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
            </template>
        </div>

        {{-- Dropdown panel --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-hidden">
            <div class="p-2 border-b border-gray-100">
                <input type="text" x-model="search" placeholder="Search..." class="w-full border border-gray-200 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-(--color-primary)">
            </div>
            <div class="overflow-y-auto max-h-52 p-1">
                <label class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 cursor-pointer text-sm font-medium text-(--color-primary)">
                    <input type="checkbox" :checked="any" @change="toggleAny()" class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                    Any
                </label>
                <div class="border-b border-gray-100 my-1"></div>
                @foreach(config('reference_data.country_list', []) as $group => $countries)
                    <p class="px-3 pt-2.5 pb-1 text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ $group }}</p>
                    @foreach($countries as $co)
                        <label x-show="matches('{{ addslashes($co) }}')" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" :checked="countries.includes('{{ addslashes($co) }}')" @change="toggle('{{ addslashes($co) }}')" class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                            {{ $co }}
                        </label>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Hidden inputs for form submission --}}
        <template x-if="any"><input type="hidden" name="{{ $cc }}[]" value="Any"></template>
        <template x-for="v in countries" :key="v"><input type="hidden" name="{{ $cc }}[]" :value="v"></template>
    </div>

    {{-- State — only when exactly one country is chosen --}}
    <div class="float-field" x-show="single && states.length > 0" x-cloak>
        <select name="{{ $sc }}" x-model="s" @change="d=''; fetchDistricts();">
            <option value="">Any</option>
            <template x-for="st in states" :key="st">
                <option :value="st" x-text="st" :selected="st === s"></option>
            </template>
        </select>
        <label>{{ $label }} State</label>
    </div>
    @if($district)
        <div class="float-field" x-show="single === 'India' && s && districts.length > 0" x-cloak>
            <select name="{{ $dc }}" x-model="d">
                <option value="">Any</option>
                <template x-for="dd in districts" :key="dd">
                    <option :value="dd" x-text="dd" :selected="dd === d"></option>
                </template>
            </select>
            <label>{{ $label }} District</label>
        </div>
    @endif
</div>
