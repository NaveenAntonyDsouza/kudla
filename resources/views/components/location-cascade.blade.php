@props(['prefix', 'label', 'district' => true])
@php
    $cc = $prefix.'_country';
    $sc = $prefix.'_state';
    $dc = $prefix.'_district';
@endphp
{{-- Country → State → District cascade (states from /api/cascade; District is India-only). --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5"
    x-data="{
        c: '{{ request($cc, '') }}',
        s: '{{ request($sc, '') }}',
        d: '{{ request($dc, '') }}',
        states: [],
        districts: [],
        async fetchStates() {
            if (!this.c) { this.states = []; this.districts = []; return; }
            if (this.c === 'India') {
                this.states = await (await fetch('/api/cascade/states')).json();
            } else {
                const r = await (await fetch(`/api/cascade/countries?country=${encodeURIComponent(this.c)}`)).json();
                this.states = r.locations || [];
            }
            if (this.s) this.fetchDistricts();
        },
        async fetchDistricts() {
            if (!this.s || this.c !== 'India') { this.districts = []; return; }
            this.districts = await (await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.s)}`)).json();
        },
        init() { if (this.c) this.fetchStates(); }
    }">
    <div class="float-field">
        <select name="{{ $cc }}" x-model="c" @change="s=''; d=''; districts=[]; fetchStates();">
            <option value="">Any</option>
            @foreach(config('reference_data.country_list', []) as $group => $countries)
                <optgroup label="{{ $group }}">
                    @foreach($countries as $co)
                        <option value="{{ $co }}">{{ $co }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <label>{{ $label }} Country</label>
    </div>
    <div class="float-field" x-show="states.length > 0" x-cloak>
        <select name="{{ $sc }}" x-model="s" @change="d=''; fetchDistricts();">
            <option value="">Any</option>
            <template x-for="st in states" :key="st">
                <option :value="st" x-text="st" :selected="st === s"></option>
            </template>
        </select>
        <label>{{ $label }} State</label>
    </div>
    @if($district)
        <div class="float-field" x-show="c === 'India' && s && districts.length > 0" x-cloak>
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
