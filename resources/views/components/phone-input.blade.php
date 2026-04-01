@props([
    'name',
    'label' => '',
    'value' => '',
    'countryCode' => '+91',
    'required' => false,
    'readonly' => false,
    'maxlength' => '15',
    'placeholder' => ' ',
    'variant' => 'default',
    'xModel' => '',
])

@php
    $codes = config('reference_data.phone_codes');
    $defaultCode = collect($codes)->firstWhere(1, $countryCode) ?? $codes[0];
    $inputId = $name . '_' . uniqid();
@endphp

@if($variant === 'login')
{{-- ── Login variant (no float-field, no label) ── --}}
<div x-data="{
    open: false,
    search: '',
    codes: {{ Js::from($codes) }},
    selected: {{ Js::from($defaultCode) }},
    flagUrl(iso) { return 'https://flagcdn.com/w40/' + iso.toLowerCase() + '.png'; },
    get filtered() {
        if (!this.search) return this.codes;
        const q = this.search.toLowerCase();
        return this.codes.filter(c => c[0].toLowerCase().includes(q) || c[1].includes(q));
    },
    pick(c) { this.selected = c; this.open = false; this.search = ''; this.$refs.phoneInput.focus(); }
}" @click.outside="open = false" class="relative">
    <div class="flex">
        <button type="button" @click="open = !open"
            class="inline-flex items-center gap-1.5 px-3 rounded-l-lg border border-r-0 border-gray-300 bg-white text-gray-700 text-sm whitespace-nowrap hover:bg-gray-50 transition-colors">
            <img :src="flagUrl(selected[2])" :alt="selected[0]" class="w-6 h-4 object-cover rounded-sm shadow-sm">
            <span x-text="selected[1]" class="font-semibold"></span>
            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <input type="tel" name="{{ $name }}" x-ref="phoneInput"
            value="{{ old($name, $value) }}"
            {{ $required ? 'required' : '' }} maxlength="{{ $maxlength }}"
            placeholder="Enter mobile number"
            {!! $xModel ? "x-model=\"{$xModel}\"" : '' !!}
            class="flex-1 border border-gray-300 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
    </div>
    <input type="hidden" name="{{ $name }}_code" :value="selected[1]">

    {{-- Dropdown --}}
    <div x-show="open" x-cloak @keydown.escape.window="open && (open = false)"
        class="absolute z-50 left-0 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-model="search" placeholder="Search country or code..." autocomplete="off"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-1 focus:ring-(--color-primary) focus:border-(--color-primary)">
        </div>
        <ul class="max-h-52 overflow-y-auto">
            <template x-for="c in filtered" :key="c[2]">
                <li @click="pick(c)"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-(--color-primary-light) transition-colors"
                    :class="selected[2] === c[2] && 'bg-(--color-primary-light) font-medium'">
                    <img :src="flagUrl(c[2])" :alt="c[0]" class="w-6 h-4 object-cover rounded-sm shadow-sm shrink-0">
                    <span x-text="c[0]" class="truncate"></span>
                    <span x-text="c[1]" class="text-gray-400 ml-auto shrink-0"></span>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">No results</li>
        </ul>
    </div>
</div>

@elseif($readonly)
{{-- ── Readonly variant (static display, no dropdown) ── --}}
<div class="float-field">
    <div class="flex">
        <span class="inline-flex items-center gap-1.5 px-3 border border-r-0 border-gray-300 rounded-l-lg bg-white text-sm text-gray-700">
            <img src="https://flagcdn.com/w40/{{ strtolower($defaultCode[2]) }}.png" alt="{{ $defaultCode[0] }}" class="w-6 h-4 object-cover rounded-sm shadow-sm">
            <span class="font-semibold">{{ $defaultCode[1] }}</span>
        </span>
        <div class="relative flex-1">
            <input type="tel" name="{{ $name }}" value="{{ $value }}" readonly
                class="border border-gray-300 rounded-r-lg w-full bg-gray-50" placeholder=" ">
            @if($label)
                <label class="left-3">{{ $label }}</label>
            @endif
        </div>
    </div>
    @error($name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

@else
{{-- ── Default variant (float-field with searchable dropdown) ── --}}
<div class="float-field relative" x-data="{
    open: false,
    search: '',
    codes: {{ Js::from($codes) }},
    selected: {{ Js::from($defaultCode) }},
    flagUrl(iso) { return 'https://flagcdn.com/w40/' + iso.toLowerCase() + '.png'; },
    get filtered() {
        if (!this.search) return this.codes;
        const q = this.search.toLowerCase();
        return this.codes.filter(c => c[0].toLowerCase().includes(q) || c[1].includes(q));
    },
    pick(c) { this.selected = c; this.open = false; this.search = ''; this.$refs.phoneInput.focus(); }
}" @click.outside="open = false">
    <div class="flex">
        <button type="button" @click="open = !open"
            class="inline-flex items-center gap-1.5 px-3 border border-r-0 border-gray-300 rounded-l-lg bg-white text-gray-700 text-sm whitespace-nowrap hover:bg-gray-50 transition-colors">
            <img :src="flagUrl(selected[2])" :alt="selected[0]" class="w-6 h-4 object-cover rounded-sm shadow-sm">
            <span x-text="selected[1]" class="font-semibold"></span>
            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="relative flex-1">
            <input type="tel" name="{{ $name }}" id="{{ $inputId }}" x-ref="phoneInput"
                value="{{ old($name, $value) }}"
                {{ $required ? 'required' : '' }} maxlength="{{ $maxlength }}"
                placeholder="{{ $placeholder }}"
                {!! $xModel ? "x-model=\"{$xModel}\"" : '' !!}
                class="border border-gray-300 rounded-r-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">
            @if($label)
                <label for="{{ $inputId }}" class="left-3">
                    {{ $label }}
                    @if($required) <span class="text-red-500">*</span> @endif
                </label>
            @endif
        </div>
    </div>
    <input type="hidden" name="{{ $name }}_code" :value="selected[1]">
    @error($name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

    {{-- Dropdown --}}
    <div x-show="open" x-cloak @keydown.escape.window="open && (open = false)"
        class="absolute z-50 left-0 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-model="search" placeholder="Search country or code..." autocomplete="off"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-1 focus:ring-(--color-primary) focus:border-(--color-primary)">
        </div>
        <ul class="max-h-52 overflow-y-auto">
            <template x-for="c in filtered" :key="c[2]">
                <li @click="pick(c)"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm cursor-pointer hover:bg-(--color-primary-light) transition-colors"
                    :class="selected[2] === c[2] && 'bg-(--color-primary-light) font-medium'">
                    <img :src="flagUrl(c[2])" :alt="c[0]" class="w-6 h-4 object-cover rounded-sm shadow-sm shrink-0">
                    <span x-text="c[0]" class="truncate"></span>
                    <span x-text="c[1]" class="text-gray-400 ml-auto shrink-0"></span>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">No results</li>
        </ul>
    </div>
</div>
@endif
