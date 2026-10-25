@props(['kind' => 'native', 'country' => '', 'state' => '', 'district' => '', 'place' => ''])
{{--
    Country → State → District → Town for profile editing (Oct 2026), the same
    cascade as registration step 4: India gets state and district dropdowns,
    other countries get their region list where we have one, and anything
    without a list falls back to a text box, so a value is never a dead end.
    Town / native place is free text with suggestions from other members.
    A saved state or district that isn't in the list is kept as an option.
    kind="working" → working_country/state/district/city,
    kind="native"  → native_country/state/district/place.
    Renders grid cells (display: contents).
--}}
@php
    $working = $kind === 'working';
    $prefix = $working ? 'working' : 'native';
    $Label = $working ? 'Working' : 'Native';
    $placeName = $working ? 'working_city' : 'native_place';
    $placeLabel = $working ? 'Working City / Town' : 'Native Place / Town / Village';
    $placesUrl = $working ? '/api/cascade/working-cities' : '/api/cascade/native-places';
@endphp
<div class="contents" x-data="{
    country: @js((string) $country), state: @js((string) $state), district: @js((string) $district), place: @js((string) $place),
    states: [], districts: [], places: [],
    async fetchStates() {
        this.states = [];
        if (!this.country) return;
        try {
            const url = this.country === 'India' ? '/api/cascade/states' : '/api/cascade/countries?country=' + encodeURIComponent(this.country);
            const data = await (await fetch(url)).json();
            this.states = Array.isArray(data) ? data : (data.locations || []);
        } catch (e) { this.states = []; }
        if (this.states.length && this.state && !this.states.includes(this.state)) this.states.unshift(this.state);
        this.fetchDistricts();
    },
    async fetchDistricts() {
        this.districts = [];
        if (this.country !== 'India' || !this.state) return;
        try { this.districts = await (await fetch('/api/cascade/districts?state=' + encodeURIComponent(this.state))).json(); } catch (e) { this.districts = []; }
        if (this.districts.length && this.district && !this.districts.includes(this.district)) this.districts.unshift(this.district);
    },
    async fetchPlaces() {
        const q = this.district ? 'district=' + encodeURIComponent(this.district) : (this.state ? 'state=' + encodeURIComponent(this.state) : '');
        if (!q) { this.places = []; return; }
        try { this.places = await (await fetch(@js($placesUrl) + '?' + q)).json(); } catch (e) { this.places = []; }
    },
    init() { if (this.country) this.fetchStates(); if (this.state) this.fetchPlaces(); }
}">
    <x-searchable-select :name="$prefix . '_country'" :label="$Label . ' Country'" :options="config('reference_data.country_list', [])" :selected="$country"
        x-model="country" x-on:change="state = ''; district = ''; districts = []; places = []; fetchStates()" />

    <div class="float-field" x-show="country">
        <template x-if="states.length > 0">
            <div>
                <select name="{{ $prefix }}_state" id="{{ $prefix }}_state" x-model="state" @change="district = ''; fetchDistricts(); fetchPlaces()">
                    <option value="">Select</option>
                    <template x-for="s in states" :key="s"><option :value="s" x-text="s" :selected="s === state"></option></template>
                </select>
                <label for="{{ $prefix }}_state">{{ $Label }} State</label>
            </div>
        </template>
        <template x-if="states.length === 0">
            <div>
                <input type="text" name="{{ $prefix }}_state" id="{{ $prefix }}_state" x-model="state" @change="fetchPlaces()" maxlength="100" placeholder=" ">
                <label for="{{ $prefix }}_state">{{ $Label }} State</label>
            </div>
        </template>
    </div>

    <div class="float-field" x-show="country === 'India' && state">
        <template x-if="districts.length > 0">
            <div>
                <select name="{{ $prefix }}_district" id="{{ $prefix }}_district" x-model="district" @change="fetchPlaces()">
                    <option value="">Select</option>
                    <template x-for="d in districts" :key="d"><option :value="d" x-text="d" :selected="d === district"></option></template>
                </select>
                <label for="{{ $prefix }}_district">{{ $Label }} District</label>
            </div>
        </template>
        <template x-if="districts.length === 0">
            <div>
                <input type="text" name="{{ $prefix }}_district" id="{{ $prefix }}_district" x-model="district" @change="fetchPlaces()" maxlength="100" placeholder=" ">
                <label for="{{ $prefix }}_district">{{ $Label }} District</label>
            </div>
        </template>
    </div>

    <div class="float-field" x-show="state || place">
        <input type="text" name="{{ $placeName }}" id="{{ $placeName }}" x-model="place" list="{{ $placeName }}_list" autocomplete="off" maxlength="100" placeholder=" ">
        <datalist id="{{ $placeName }}_list"><template x-for="pl in places" :key="pl"><option :value="pl"></option></template></datalist>
        <label for="{{ $placeName }}">{{ $placeLabel }}</label>
    </div>
</div>
