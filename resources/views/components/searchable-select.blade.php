@props(['name', 'label', 'options' => [], 'selected' => null, 'id' => null, 'required' => false])

{{--
    Single-choice dropdown with a search box, for long lists (education,
    occupation, country). The native <select> stays underneath and is what
    submits, so .float-field styling, `required` validation and no-JS
    behaviour are unchanged; Alpine lays a transparent button over it that
    opens a searchable panel. Options may be grouped (['Group' => [..]]) or
    flat. A saved value that's no longer in the list is kept as its own
    option, so editing a profile never silently blanks it.

    Extra attributes go on the <select>, so a parent form can bind it:
    x-model="workingCountry" x-on:change="fetchWorkingStates()". Picking
    fires a real `change` event. State/refs are prefixed `ss` so they never
    shadow the parent's Alpine names.
--}}
@php
    $id ??= $name;
    $options = is_iterable($options) ? $options : [];
    $groups = [];
    foreach ($options as $group => $items) {
        if (is_iterable($items)) {
            $groups[] = ['label' => preg_replace('/^\d+\.\s*/', '', (string) $group), 'items' => array_values(array_map('strval', (array) $items))];
        } elseif (($last = array_key_last($groups)) !== null && $groups[$last]['label'] === '') {
            $groups[$last]['items'][] = (string) $items; // flat list: one unlabelled group
        } else {
            $groups[] = ['label' => '', 'items' => [(string) $items]];
        }
    }
    $selected = (string) ($selected ?? '');
    $known = collect($groups)->pluck('items')->flatten()->all();
    if ($selected !== '' && ! in_array($selected, $known, true)) {
        array_unshift($groups, ['label' => '', 'items' => [$selected]]);
    }
@endphp

<div class="float-field" x-data="{
        ssOpen: false,
        ssReady: false,
        ssSearch: '',
        ssValue: {{ Js::from($selected) }},
        ssGroups: {{ Js::from($groups) }},
        get ssFiltered() {
            const q = this.ssSearch.trim().toLowerCase();
            if (!q) return this.ssGroups;
            return this.ssGroups
                .map(g => ({ label: g.label, items: g.label.toLowerCase().includes(q) ? g.items : g.items.filter(i => i.toLowerCase().includes(q)) }))
                .filter(g => g.items.length);
        },
        ssShow() {
            this.ssValue = this.$refs.ssSelect.value;
            this.ssOpen = true;
            this.ssSearch = '';
            this.$nextTick(() => {
                this.$refs.ssSearch.focus();
                this.$refs.ssList.querySelector('[data-current]')?.scrollIntoView({ block: 'center' });
            });
        },
        ssPick(v) {
            this.ssValue = v;
            this.$refs.ssSelect.value = v;
            this.$refs.ssSelect.dispatchEvent(new Event('change', { bubbles: true }));
            this.ssOpen = false;
        },
        ssPickFirst() {
            const first = this.ssFiltered[0]?.items[0];
            if (first) this.ssPick(first);
        },
    }" x-init="ssReady = true" @click.outside="ssOpen = false" @keydown.escape="ssOpen = false">

    <select name="{{ $name }}" id="{{ $id }}" x-ref="ssSelect" :tabindex="ssReady ? -1 : null" @if($required) required @endif {{ $attributes }}>
        <option value="">Select</option>
        @foreach($groups as $group)
            @if($group['label'] !== '')<optgroup label="{{ $group['label'] }}">@endif
            @foreach($group['items'] as $opt)
                <option value="{{ $opt }}" @selected($selected === $opt)>{{ $opt }}</option>
            @endforeach
            @if($group['label'] !== '')</optgroup>@endif
        @endforeach
    </select>
    <label for="{{ $id }}">{!! $label !!}</label>

    {{-- Opens the searchable panel instead of the long native list --}}
    <button type="button" x-show="ssReady" style="display: none" @click="ssShow()" class="absolute inset-0 w-full cursor-pointer rounded-lg"
        aria-haspopup="listbox" :aria-expanded="ssOpen" aria-label="{{ strip_tags($label) }}"></button>

    <div x-show="ssOpen" style="display: none"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-ref="ssSearch" x-model="ssSearch" @keydown.enter.prevent="ssPickFirst()" placeholder="Type to search"
                autocomplete="off" aria-label="Search {{ strip_tags($label) }}"
                style="padding: 8px 10px; font-size: 14px;" class="w-full border border-gray-200 rounded">
        </div>
        <div x-ref="ssList" role="listbox" class="overflow-y-auto max-h-64 p-1 text-sm">
            <template x-for="(g, gi) in ssFiltered" :key="gi + g.label">
                <div>
                    <div x-show="g.label" x-text="g.label" class="px-3 pt-2.5 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider"></div>
                    <template x-for="item in g.items" :key="g.label + item">
                        <button type="button" role="option" @click="ssPick(item)" :aria-selected="item === ssValue" :data-current="item === ssValue ? '' : null"
                            class="block w-full text-left px-3 py-2 rounded hover:bg-gray-50"
                            :class="item === ssValue ? 'font-medium' : 'text-gray-800'"
                            {{-- inline: the live CSS build (not rebuilt on deploy) lacks bg-(--color-primary-light) --}}
                            :style="item === ssValue ? 'background: var(--brand-primary-light, #F3E8F7); color: var(--brand-primary, #8B1D91)' : ''"
                            x-text="item"></button>
                    </template>
                </div>
            </template>
            <p x-show="ssFiltered.length === 0" class="px-3 py-3 text-gray-500">No match. Try a shorter word.</p>
        </div>
    </div>

    {{ $slot }}
</div>
