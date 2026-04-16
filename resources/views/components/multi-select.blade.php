@props(['name', 'label', 'options' => [], 'selected' => [], 'searchable' => false, 'grouped' => false, 'emitTo' => null, 'showAny' => true])

@php
    $selectedArr = is_array($selected) ? $selected : [];
    $flatOptions = [];
    $groupMap = [];
    if ($grouped) {
        foreach ($options as $group => $items) {
            $groupMap[$group] = $items;
            foreach ($items as $item) {
                $flatOptions[] = $item;
            }
        }
    } else {
        $flatOptions = $options;
    }
@endphp

<div x-data="{
    open: false,
    search: '',
    selected: {{ Js::from(old($name, $selectedArr)) }},
    allOptions: {{ Js::from($flatOptions) }},
    groupMap: {{ Js::from($groupMap) }},

    get isAny() {
        return this.selected.includes('Any');
    },

    toggle(val) {
        if (val === 'Any') {
            if (this.isAny) {
                this.selected = [];
            } else {
                this.selected = ['Any', ...this.allOptions];
            }
        } else {
            if (this.selected.includes(val)) {
                this.selected = this.selected.filter(v => v !== val && v !== 'Any');
            } else {
                this.selected = this.selected.filter(v => v !== 'Any');
                this.selected.push(val);
                if (this.selected.length === this.allOptions.length) {
                    this.selected = ['Any', ...this.allOptions];
                }
            }
        }
    },

    remove(val) {
        this.selected = this.selected.filter(v => v !== val && v !== 'Any');
    },

    get displayText() {
        if (this.selected.length === 0) return '';
        if (this.isAny) return 'Any';
        return this.selected.length + ' selected';
    },

    toggleGroup(groupName) {
        const items = this.groupMap[groupName] || [];
        const allSelected = items.every(i => this.selected.includes(i));
        this.selected = this.selected.filter(v => v !== 'Any');
        if (allSelected) {
            this.selected = this.selected.filter(v => !items.includes(v));
        } else {
            items.forEach(i => { if (!this.selected.includes(i)) this.selected.push(i); });
        }
        if (this.selected.length === this.allOptions.length) {
            this.selected = ['Any', ...this.allOptions];
        }
    },

    isGroupSelected(groupName) {
        const items = this.groupMap[groupName] || [];
        return items.length > 0 && items.every(i => this.selected.includes(i));
    },

    matchesSearch(item) {
        if (!this.search) return true;
        return item.toLowerCase().includes(this.search.toLowerCase());
    },
    init() {
        this.$watch('selected', (val) => {
            @if($emitTo)
                this.$dispatch('multiselect-change', { name: '{{ $name }}', value: val });
            @endif
        });
    }
}" @click.away="open = false" @multiselect-clear.window="if ($event.detail.name === '{{ $name }}') { selected = []; }" class="relative">

    {{-- Label --}}
    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $label }}</label>

    {{-- Trigger --}}
    <button type="button" @click="open = !open"
        class="w-full flex items-center justify-between border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-left bg-white hover:border-gray-400 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) transition-colors">
        <span :class="selected.length === 0 ? 'text-gray-400' : 'text-gray-900'" x-text="displayText || 'Select'"></span>
        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Selected Tags --}}
    <div x-show="selected.length > 0 && !isAny" class="flex flex-wrap gap-1.5 mt-2">
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
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Any (All selected)</span>
    </div>

    {{-- Dropdown Panel --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-hidden">

        @if($searchable)
            <div class="p-2 border-b border-gray-100">
                <input type="text" x-model="search" placeholder="Search..." class="w-full border border-gray-200 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-(--color-primary)">
            </div>
        @endif

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

            @if($grouped)
                @foreach($options as $group => $items)
                    <label class="flex items-center gap-2 px-3 pt-2.5 pb-1 cursor-pointer hover:bg-gray-50 rounded">
                        <input type="checkbox" :checked="isGroupSelected('{{ addslashes($group) }}')" @change="toggleGroup('{{ addslashes($group) }}')"
                            class="rounded border-gray-300 text-(--color-secondary) focus:ring-(--color-secondary)">
                        <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ $group }}</span>
                    </label>
                    @foreach($items as $item)
                        <label x-show="matchesSearch('{{ addslashes($item) }}')" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" :checked="selected.includes('{{ addslashes($item) }}')" @change="toggle('{{ addslashes($item) }}')"
                                class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                            {{ $item }}
                        </label>
                    @endforeach
                @endforeach
            @else
                @foreach($options as $option)
                    <label x-show="matchesSearch('{{ addslashes($option) }}')" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                        <input type="checkbox" :checked="selected.includes('{{ addslashes($option) }}')" @change="toggle('{{ addslashes($option) }}')"
                            class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                        {{ $option }}
                    </label>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Hidden inputs for form submission --}}
    <template x-for="val in selected" :key="val">
        <input type="hidden" :name="'{{ $name }}[]'" :value="val">
    </template>
</div>
