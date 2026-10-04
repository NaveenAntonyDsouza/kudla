@props(['name', 'label', 'options' => [], 'selected' => null, 'id' => null, 'required' => false])

{{--
    Single-choice dropdown with a search box, for long lists (education,
    occupation). The native <select> stays underneath and is what submits,
    so .float-field styling, `required` validation and no-JS behaviour are
    unchanged; Alpine lays a transparent button over it that opens a
    searchable panel. Options may be grouped (['Group' => [..]]) or flat.
    A saved value that's no longer in the list is kept as its own option,
    so editing a profile never silently blanks it.
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
        open: false,
        ready: false,
        search: '',
        value: {{ Js::from($selected) }},
        groups: {{ Js::from($groups) }},
        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.groups;
            return this.groups
                .map(g => ({ label: g.label, items: g.label.toLowerCase().includes(q) ? g.items : g.items.filter(i => i.toLowerCase().includes(q)) }))
                .filter(g => g.items.length);
        },
        show() {
            this.open = true;
            this.search = '';
            this.$nextTick(() => {
                this.$refs.search.focus();
                this.$refs.list.querySelector('[data-current]')?.scrollIntoView({ block: 'center' });
            });
        },
        pick(v) {
            this.value = v;
            this.$refs.select.value = v;
            this.$refs.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.open = false;
        },
        pickFirst() {
            const first = this.filtered[0]?.items[0];
            if (first) this.pick(first);
        },
    }" x-init="ready = true" @click.outside="open = false" @keydown.escape="open = false">

    <select name="{{ $name }}" id="{{ $id }}" x-ref="select" @change="value = $event.target.value" :tabindex="ready ? -1 : null" @if($required) required @endif>
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
    <button type="button" x-show="ready" style="display: none" @click="show()" class="absolute inset-0 w-full cursor-pointer rounded-lg"
        aria-haspopup="listbox" :aria-expanded="open" aria-label="{{ strip_tags($label) }}"></button>

    <div x-show="open" style="display: none"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
        <div class="p-2 border-b border-gray-100">
            <input type="text" x-ref="search" x-model="search" @keydown.enter.prevent="pickFirst()" placeholder="Type to search"
                autocomplete="off" aria-label="Search {{ strip_tags($label) }}"
                style="padding: 8px 10px; font-size: 14px;" class="w-full border border-gray-200 rounded">
        </div>
        <div x-ref="list" role="listbox" class="overflow-y-auto max-h-64 p-1 text-sm">
            <template x-for="(g, gi) in filtered" :key="gi + g.label">
                <div>
                    <div x-show="g.label" x-text="g.label" class="px-3 pt-2.5 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider"></div>
                    <template x-for="item in g.items" :key="g.label + item">
                        <button type="button" role="option" @click="pick(item)" :aria-selected="item === value" :data-current="item === value ? '' : null"
                            class="block w-full text-left px-3 py-2 rounded hover:bg-gray-50"
                            :class="item === value ? 'font-medium' : 'text-gray-800'"
                            {{-- inline: the live CSS build (not rebuilt on deploy) lacks bg-(--color-primary-light) --}}
                            :style="item === value ? 'background: var(--brand-primary-light, #F3E8F7); color: var(--brand-primary, #8B1D91)' : ''"
                            x-text="item"></button>
                    </template>
                </div>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-3 text-gray-500">No match. Try a shorter word, or pick the closest one and add details in the next box.</p>
        </div>
    </div>

    {{ $slot }}
</div>
