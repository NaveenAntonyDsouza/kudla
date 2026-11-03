@props(['name', 'label', 'options' => [], 'selected' => [], 'searchable' => false, 'grouped' => false, 'emitTo' => null, 'showAny' => true, 'labelClass' => 'block text-xs font-medium text-gray-500 mb-1'])

@php
    // Tolerate option data whose shape doesn't match the $grouped flag.
    // Admin overrides (GatewayConfigProvider / reference_data_options) can
    // store a flat list for a category the view marks as grouped; without
    // this guard the nested foreach below fatals on a string group value.
    $options = is_iterable($options) ? $options : [];
    $selectedArr = is_array($selected) ? $selected : [];

    $isGrouped = $grouped;
    if ($isGrouped) {
        foreach ($options as $items) {
            if (! is_iterable($items)) { $isGrouped = false; break; }
        }
    }

    $flatOptions = [];
    $groupList = []; // [{label, items}] in list order, for the x-for below
    if ($isGrouped) {
        foreach ($options as $group => $items) {
            $groupList[] = ['label' => (string) $group, 'items' => array_values((array) $items)];
            foreach ($items as $item) {
                $flatOptions[] = $item;
            }
        }
    } else {
        foreach ($options as $opt) {
            if (is_iterable($opt)) {
                foreach ($opt as $o) { $flatOptions[] = $o; }
            } else {
                $flatOptions[] = $opt;
            }
        }
    }
@endphp

{{-- "Any" (Nov 2026) means no limit: nothing chosen, and it is what an untouched field shows. It used to
     tick every option, so "Any" on income or occupation quietly left out members who never filled them in. --}}
<div x-data="{
    open: false,
    search: '',
    anyAllowed: {{ $showAny ? 'true' : 'false' }},
    selected: {{ Js::from(old($name, $selectedArr)) }},
    hadChoice: false,
    // one copy of the options: a flat list, or groups (flattened in init)
    allOptions: {{ Js::from($isGrouped ? [] : $flatOptions) }},
    groupList: {{ Js::from($groupList) }},

    itemsOf(groupName) {
        return (this.groupList.find(g => g.label === groupName) || {}).items || [];
    },

    get isAny() {
        return this.anyAllowed && (this.selected.length === 0 || this.selected.includes('Any'));
    },

    toggle(val) {
        if (val === 'Any') {
            this.selected = [];
            return;
        }
        if (this.selected.includes(val)) {
            this.selected = this.selected.filter(v => v !== val && v !== 'Any');
        } else {
            this.selected = this.selected.filter(v => v !== 'Any');
            this.selected.push(val);
            this.anyIfAll();
        }
    },

    // every option ticked is the same as no limit
    anyIfAll() {
        if (this.anyAllowed && this.allOptions.length && this.allOptions.every(o => this.selected.includes(o))) {
            this.selected = [];
        }
    },

    // what the form sends: the choices; for Any, nothing, or one 'Any' when it replaces earlier
    // choices (pages that save only what is sent would otherwise keep the old ones)
    get sent() {
        return this.isAny ? (this.hadChoice ? ['Any'] : []) : this.selected;
    },

    remove(val) {
        this.selected = this.selected.filter(v => v !== val && v !== 'Any');
    },

    get displayText() {
        if (this.isAny) return 'Any';
        if (this.selected.length === 0) return '';
        return this.selected.length + ' selected';
    },

    toggleGroup(groupName) {
        const items = this.itemsOf(groupName);
        const allSelected = items.every(i => this.selected.includes(i));
        this.selected = this.selected.filter(v => v !== 'Any');
        if (allSelected) {
            this.selected = this.selected.filter(v => !items.includes(v));
        } else {
            items.forEach(i => { if (!this.selected.includes(i)) this.selected.push(i); });
        }
        this.anyIfAll();
    },

    isGroupSelected(groupName) {
        const items = this.itemsOf(groupName);
        return items.length > 0 && items.every(i => this.selected.includes(i));
    },

    matchesSearch(item) {
        if (!this.search) return true;
        return item.toLowerCase().includes(this.search.toLowerCase());
    },
    init() {
        if (this.groupList.length) this.allOptions = this.groupList.flatMap(g => g.items);
        this.hadChoice = this.selected.length > 0;
        // saved before Nov 2026: 'Any' plus every option
        if (this.selected.includes('Any')) this.selected = [];
        this.anyIfAll();
        this.$watch('selected', (val) => {
            @if($emitTo)
                this.$dispatch('multiselect-change', { name: '{{ $name }}', value: val });
            @endif
        });
    }
}" @click.away="open = false" @multiselect-clear.window="if ($event.detail.name === '{{ $name }}') { selected = []; }" class="relative">

    {{-- Label. It, the "Any" pill and the chosen values open the list too: on Partner Preferences
         15 members tapped something 59 times in 30 days with nothing happening (weekly review, 4 Nov 2026) --}}
    <label class="{{ $labelClass }} cursor-pointer" @click="open = !open">{{ $label }}</label>

    {{-- Trigger --}}
    <button type="button" @click="open = !open"
        class="w-full flex items-center justify-between border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-left bg-white hover:border-gray-400 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) transition-colors">
        <span :class="displayText ? 'text-gray-900' : 'text-gray-400'" x-text="displayText || 'Select'"></span>
        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Selected Tags --}}
    <div x-show="selected.length > 0 && !isAny" class="flex flex-wrap gap-1.5 mt-2 cursor-pointer" @click="open = true">
        <template x-for="val in selected.filter(v => v !== 'Any')" :key="val">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-(--color-primary-light) text-(--color-primary)">
                <span x-text="val" class="max-w-[120px] truncate"></span>
                <button type="button" @click.stop="remove(val)" class="hover:text-red-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>
        </template>
    </div>
    <div x-show="isAny" class="mt-2">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 cursor-pointer" @click="open = true">Any</span>
    </div>

    {{-- Dropdown Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-hidden">

        @if($searchable)
            <div class="p-2 border-b border-gray-100">
                <input type="text" x-model="search" placeholder="Search..." class="w-full border border-gray-200 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-(--color-primary)">
            </div>
        @endif

        {{-- Rows are built from the data above only while the list is open (Oct 2026: the
             partner-preferences page carried ~0.9 MB of option HTML for its 24 lists) --}}
        <template x-if="open">
        <div class="overflow-y-auto max-h-52 p-1">
            {{-- Any Option --}}
            @if($showAny)
                <label class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 cursor-pointer text-sm font-medium text-(--color-primary)">
                    <input type="checkbox" :checked="isAny" @change="toggle('Any')"
                        class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                    Any
                </label>
                <div class="border-b border-gray-100 my-1"></div>
            @endif

            @if($isGrouped)
                <template x-for="(g, gi) in groupList" :key="gi">
                    <div>
                        <label class="flex items-center gap-2 px-3 pt-2.5 pb-1 cursor-pointer hover:bg-gray-50 rounded">
                            <input type="checkbox" :checked="isGroupSelected(g.label)" @change="toggleGroup(g.label)"
                                class="rounded border-gray-300 text-(--color-secondary) focus:ring-(--color-secondary)">
                            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider" x-text="g.label"></span>
                        </label>
                        <template x-for="(item, i) in g.items" :key="i">
                            <label x-show="!search || item.toLowerCase().includes(search.toLowerCase())" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" :checked="selected.includes(item)" @change="toggle(item)"
                                    class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                                <span x-text="item"></span>
                            </label>
                        </template>
                    </div>
                </template>
            @else
                <template x-for="(item, i) in allOptions" :key="i">
                    <label x-show="!search || item.toLowerCase().includes(search.toLowerCase())" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                        <input type="checkbox" :checked="selected.includes(item)" @change="toggle(item)"
                            class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                        <span x-text="item"></span>
                    </label>
                </template>
            @endif
        </div>
        </template>
    </div>

    {{-- Hidden inputs for form submission --}}
    <template x-for="val in sent" :key="val">
        <input type="hidden" :name="'{{ $name }}[]'" :value="val">
    </template>
</div>
