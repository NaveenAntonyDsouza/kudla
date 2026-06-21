<x-layouts.app title="Search Profiles">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        activeTab: '{{ $activeTab }}',
        religions: [],
        moreOpen: false,
        hasReligion(r) { return this.religions.includes(r); }
    }">

        <p class="text-sm text-gray-500 mb-6">
            <a href="/" class="hover:text-(--color-primary)">Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Search</span>
        </p>

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- ══ LEFT NAV ══ --}}
            <div class="hidden lg:block lg:w-48 shrink-0">
                <div class="sticky top-24 space-y-1">
                    <h2 class="text-base font-semibold text-gray-900 mb-3">Search</h2>
                    <button @click="activeTab = 'partner'"
                        :class="activeTab === 'partner' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        Quick Search
                    </button>
                    <button @click="activeTab = 'advance'"
                        :class="activeTab === 'advance' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                        Advance Search
                    </button>
                    <button @click="activeTab = 'keyword'"
                        :class="activeTab === 'keyword' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                        Keyword Search
                    </button>
                    <button @click="activeTab = 'byid'"
                        :class="activeTab === 'byid' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
                        Search by ID
                    </button>
                </div>
            </div>

            {{-- ══ Mobile tabs ══ --}}
            <div class="lg:hidden flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4">
                <button @click="activeTab = 'partner'"
                    :class="activeTab === 'partner' ? 'bg-(--color-primary) text-white' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors">Quick Search</button>
                <button @click="activeTab = 'advance'"
                    :class="activeTab === 'advance' ? 'bg-(--color-primary) text-white' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors">Advance Search</button>
                <button @click="activeTab = 'keyword'"
                    :class="activeTab === 'keyword' ? 'bg-(--color-primary) text-white' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors">Keyword</button>
                <button @click="activeTab = 'byid'"
                    :class="activeTab === 'byid' ? 'bg-(--color-primary) text-white' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors">By ID</button>
            </div>

            {{-- ══ RIGHT: CONTENT ══ --}}
            <div class="flex-1 min-w-0">

                {{-- Login CTA --}}
                <div class="bg-(--color-primary-light) rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-700"><a href="/register" class="font-semibold text-(--color-primary) hover:underline">Register free</a> or <a href="/login" class="font-semibold text-(--color-primary) hover:underline">login</a> to use advanced filters and view full profiles.</p>
                </div>

                {{-- ── QUICK SEARCH TAB ── --}}
                <div x-show="activeTab === 'partner'" x-cloak>
                    @php
                        // Caste can arrive as an array (multi-select) or a legacy
                        // single string. 'Any' is the explicit "no caste filter".
                        $quickCasteRaw = (array) request('caste', []);
                        $quickCasteAny = in_array('Any', $quickCasteRaw, true);
                        $quickCasteSelected = array_values(array_filter($quickCasteRaw, fn($c) => $c !== '' && $c !== 'Any'));
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6"
                         x-data="{
                            selectedReligion: '{{ request('religion', '') }}',
                            communities: [],
                            selectedCastes: {{ Js::from($quickCasteSelected) }},
                            casteAny: {{ $quickCasteAny ? 'true' : 'false' }},
                            casteOpen: false,
                            casteSearch: '',
                            loading: false,
                            async fetchCommunities() {
                                if (!this.selectedReligion) {
                                    this.communities = [];
                                    this.selectedCastes = [];
                                    this.casteAny = false;
                                    return;
                                }
                                this.loading = true;
                                try {
                                    const res = await fetch('/api/cascade/communities?religion=' + encodeURIComponent(this.selectedReligion));
                                    const data = await res.json();
                                    this.communities = data.map(c => c.community_name);
                                    // Drop any previously-picked communities that
                                    // don't belong to the newly chosen religion.
                                    this.selectedCastes = this.selectedCastes.filter(c => this.communities.includes(c));
                                } catch(e) {
                                    this.communities = [];
                                }
                                this.loading = false;
                            },
                            toggleCaste(val) {
                                if (this.selectedCastes.includes(val)) {
                                    this.selectedCastes = this.selectedCastes.filter(c => c !== val);
                                } else {
                                    this.casteAny = false;
                                    this.selectedCastes.push(val);
                                }
                            },
                            toggleCasteAny() {
                                this.casteAny = !this.casteAny;
                                if (this.casteAny) this.selectedCastes = [];
                            },
                            removeCaste(val) {
                                this.selectedCastes = this.selectedCastes.filter(c => c !== val);
                            },
                            casteMatches(item) {
                                if (!this.casteSearch) return true;
                                return item.toLowerCase().includes(this.casteSearch.toLowerCase());
                            },
                            get casteDisplay() {
                                if (this.casteAny) return 'Any';
                                if (this.selectedCastes.length === 0) return '';
                                return this.selectedCastes.length + ' selected';
                            },
                            init() {
                                if (this.selectedReligion) this.fetchCommunities();
                            }
                         }">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Quick Search</h2>

                        <form method="GET" action="{{ route('search.results.public') }}">
                            <input type="hidden" name="search_type" value="quick">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Gender --}}
                                <div class="float-field">
                                    <select name="gender">
                                        <option value="">Any</option>
                                        <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Groom</option>
                                        <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Bride</option>
                                    </select>
                                    <label>Looking for</label>
                                </div>

                                {{-- Age Range --}}
                                <div class="float-field">
                                    <select name="age_from">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_from', 21) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age From</label>
                                </div>
                                <div class="float-field">
                                    <select name="age_to">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_to', 35) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age To</label>
                                </div>

                                {{-- Height Range --}}
                                <div class="float-field">
                                    <select name="height_from">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_from') === $h ? 'selected' : '' }}>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                    <label>Height From</label>
                                </div>
                                <div class="float-field">
                                    <select name="height_to">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_to') === $h ? 'selected' : '' }}>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                    <label>Height To</label>
                                </div>

                                {{-- Religion --}}
                                <div class="float-field">
                                    <select name="religion" x-model="selectedReligion" @change="fetchCommunities()">
                                        <option value="">Any Religion</option>
                                        @foreach(['Christian', 'Hindu', 'Muslim', 'Jain', 'Sikh', 'Buddhist', 'Other'] as $r)
                                            <option value="{{ $r }}">{{ $r }}</option>
                                        @endforeach
                                    </select>
                                    <label>Religion</label>
                                </div>
                            </div>

                            {{-- Caste/Community (multi-select, cascaded from Religion) --}}
                            <div class="mt-5" x-show="selectedReligion" x-transition>
                                <div class="relative" @click.away="casteOpen = false">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Caste / Community</label>

                                    {{-- Trigger --}}
                                    <button type="button" @click="casteOpen = !casteOpen"
                                        class="w-full flex items-center justify-between border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-left bg-white hover:border-gray-400 focus:border-(--color-primary) focus:ring-1 focus:ring-(--color-primary) transition-colors">
                                        <span :class="(casteAny || selectedCastes.length) ? 'text-gray-900' : 'text-gray-400'" x-text="casteDisplay || 'Select'"></span>
                                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="casteOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    {{-- Selected Tags --}}
                                    <div x-show="selectedCastes.length > 0 && !casteAny" class="flex flex-wrap gap-1.5 mt-2">
                                        <template x-for="val in selectedCastes" :key="val">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-(--color-primary-light) text-(--color-primary)">
                                                <span x-text="val" class="max-w-[120px] truncate"></span>
                                                <button type="button" @click.stop="removeCaste(val)" class="hover:text-red-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                    <div x-show="casteAny" class="mt-2">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Any (All communities)</span>
                                    </div>

                                    {{-- Dropdown Panel --}}
                                    <div x-show="casteOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-hidden">
                                        <div class="p-2 border-b border-gray-100">
                                            <input type="text" x-model="casteSearch" placeholder="Search..." class="w-full border border-gray-200 rounded px-2.5 py-1.5 text-sm focus:outline-none focus:border-(--color-primary)">
                                        </div>
                                        <div class="overflow-y-auto max-h-52 p-1">
                                            {{-- Any Option --}}
                                            <label class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 cursor-pointer text-sm font-medium text-(--color-primary)">
                                                <input type="checkbox" :checked="casteAny" @change="toggleCasteAny()"
                                                    class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                                                Any
                                            </label>
                                            <div class="border-b border-gray-100 my-1"></div>
                                            <template x-for="c in communities" :key="c">
                                                <label x-show="casteMatches(c)" class="flex items-center gap-2 px-3 py-1.5 rounded hover:bg-gray-50 cursor-pointer text-sm">
                                                    <input type="checkbox" :checked="selectedCastes.includes(c)" @change="toggleCaste(c)"
                                                        class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                                                    <span x-text="c"></span>
                                                </label>
                                            </template>
                                            <p x-show="communities.length === 0 && !loading" class="px-3 py-2 text-sm text-gray-400">No communities found</p>
                                        </div>
                                    </div>

                                    {{-- Hidden inputs for form submission --}}
                                    <template x-if="casteAny">
                                        <input type="hidden" name="caste[]" value="Any">
                                    </template>
                                    <template x-for="val in selectedCastes" :key="val">
                                        <input type="hidden" name="caste[]" :value="val">
                                    </template>
                                </div>
                            </div>

                            {{-- Mother Tongue (multi-select) --}}
                            <div class="mt-5">
                                <x-multi-select name="mother_tongue" label="Mother Tongue"
                                    :options="config('reference_data.language_list', [])"
                                    :selected="(array) request('mother_tongue', [])"
                                    :searchable="true" />
                            </div>

                            {{-- Location (Working + Native) — all 6 fields. District populates for India only. --}}
                            <div class="mt-5 space-y-5">
                                <x-location-cascade prefix="working" label="Working" :district="true" />
                                <x-location-cascade prefix="native" label="Native" :district="true" />
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-8 pt-4 border-t border-gray-200">
                                <a href="{{ route('search.quick') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-center">Clear</a>
                                <button type="submit" class="w-full sm:w-auto px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── ADVANCE SEARCH TAB ── --}}
                <div x-show="activeTab === 'advance'" x-cloak>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Advance Search</h2>

                        <form method="GET" action="{{ route('search.results.public') }}">
                            <input type="hidden" name="search_type" value="advance">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Gender --}}
                                <div class="float-field">
                                    <select name="gender">
                                        <option value="">Any</option>
                                        <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Groom</option>
                                        <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Bride</option>
                                    </select>
                                    <label>Looking for</label>
                                </div>

                                {{-- Age Range --}}
                                <div class="float-field">
                                    <select name="age_from">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_from', 21) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age From</label>
                                </div>
                                <div class="float-field">
                                    <select name="age_to">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_to', 35) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age To</label>
                                </div>

                                {{-- Height Range --}}
                                <div class="float-field">
                                    <select name="height_from">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_from') === $h ? 'selected' : '' }}>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                    <label>Height From</label>
                                </div>
                                <div class="float-field">
                                    <select name="height_to">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_to') === $h ? 'selected' : '' }}>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                    <label>Height To</label>
                                </div>
                            </div>

                            {{-- Marital Status --}}
                            <div class="mt-5">
                                <x-multi-select name="marital_status" label="Marital Status"
                                    :options="['Unmarried', 'Widow/Widower', 'Divorced', 'Separated', 'Annulled']"
                                    :selected="(array) request('marital_status', [])" />
                            </div>

                            {{-- Religion --}}
                            <div class="mt-5">
                                <x-multi-select name="religion" label="Religion"
                                    :options="['Christian', 'Hindu', 'Muslim', 'Jain', 'Sikh', 'Buddhist', 'Other']"
                                    :selected="(array) request('religion', [])"
                                    emitTo="religions" />
                            </div>

                            {{-- Denomination (Christian) --}}
                            <div x-show="hasReligion('Christian')" class="mt-5">
                                <x-multi-select name="denomination" label="Denomination"
                                    :options="config('reference_data.denomination_list', [])"
                                    :selected="(array) request('denomination', [])" :grouped="true" :searchable="true" />
                            </div>

                            {{-- Caste (Hindu/Jain) --}}
                            <div x-show="hasReligion('Hindu') || hasReligion('Jain')" class="mt-5">
                                <x-multi-select name="caste" label="Caste"
                                    :options="\App\Models\Community::getCasteList()"
                                    :selected="(array) request('caste', [])" :searchable="true" />
                            </div>

                            {{-- Education & Occupation --}}
                            <div class="mt-5 space-y-5">
                                <x-multi-select name="education" label="Education"
                                    :options="config('reference_data.educational_qualifications_list', [])"
                                    :selected="(array) request('education', [])" :grouped="true" :searchable="true" />

                                <x-multi-select name="occupation" label="Occupation"
                                    :options="config('reference_data.occupation_category_list', [])"
                                    :selected="(array) request('occupation', [])" :grouped="true" :searchable="true" />
                            </div>

                            {{-- Location. Working country → state → district cascade
                                 (states/districts from /api/cascade endpoints). --}}
                            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-5"
                                x-data="{
                                    wCountry: '{{ request('working_country', '') }}',
                                    wState: '{{ request('working_state', '') }}',
                                    wDistrict: '{{ request('working_district', '') }}',
                                    wStates: [],
                                    wDistricts: [],
                                    async fetchWStates() {
                                        if (!this.wCountry) { this.wStates = []; this.wDistricts = []; return; }
                                        if (this.wCountry === 'India') {
                                            this.wStates = await (await fetch('/api/cascade/states')).json();
                                        } else {
                                            const data = await (await fetch(`/api/cascade/countries?country=${encodeURIComponent(this.wCountry)}`)).json();
                                            this.wStates = data.locations || [];
                                        }
                                        if (this.wState) this.fetchWDistricts();
                                    },
                                    async fetchWDistricts() {
                                        if (!this.wState || this.wCountry !== 'India') { this.wDistricts = []; return; }
                                        this.wDistricts = await (await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.wState)}`)).json();
                                    },
                                    init() { if (this.wCountry) this.fetchWStates(); }
                                }">
                                <div class="float-field">
                                    <select name="working_country" x-model="wCountry" @change="wState=''; wDistrict=''; wDistricts=[]; fetchWStates();">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.country_list') as $group => $countries)
                                            <optgroup label="{{ $group }}">
                                                @foreach($countries as $c)
                                                    <option value="{{ $c }}">{{ $c }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <label>Working Country</label>
                                </div>
                                <div class="float-field" x-show="wStates.length > 0" x-cloak>
                                    <select name="working_state" x-model="wState" @change="wDistrict=''; fetchWDistricts();">
                                        <option value="">Any</option>
                                        <template x-for="st in wStates" :key="st">
                                            <option :value="st" x-text="st" :selected="st === wState"></option>
                                        </template>
                                    </select>
                                    <label>Working State</label>
                                </div>
                                <div class="float-field" x-show="wCountry === 'India' && wState && wDistricts.length > 0" x-cloak>
                                    <select name="working_district" x-model="wDistrict">
                                        <option value="">Any</option>
                                        <template x-for="d in wDistricts" :key="d">
                                            <option :value="d" x-text="d" :selected="d === wDistrict"></option>
                                        </template>
                                    </select>
                                    <label>Working District</label>
                                </div>
                                <div class="float-field">
                                    <select name="mother_tongue">
                                        <option value="">Any Language</option>
                                        @foreach(config('reference_data.language_list', []) as $lang)
                                            <option value="{{ $lang }}" {{ request('mother_tongue') === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                                        @endforeach
                                    </select>
                                    <label>Mother Tongue</label>
                                </div>
                            </div>

                            {{-- Native Country / State --}}
                            <div class="mt-5">
                                <x-location-cascade prefix="native" label="Native" :district="false" />
                            </div>

                            {{-- More Criteria --}}
                            <div class="mt-6 border-t border-gray-100 pt-4">
                                <button type="button" @click="moreOpen = !moreOpen" class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                                    Add More Criteria
                                    <svg class="w-4 h-4 transition-transform" :class="moreOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="moreOpen" x-cloak class="mt-4 space-y-5">
                                    <x-multi-select name="physical_status" label="Physical Status" :options="['Normal', 'Differently Abled']" :selected="(array) request('physical_status', [])" />
                                    <x-multi-select name="family_status" label="Family Status" :options="['Middle Class', 'Upper Middle Class', 'Rich', 'Affluent']" :selected="(array) request('family_status', [])" />
                                    <x-multi-select name="body_type" label="Body Type" :options="['Slim', 'Average', 'Athletic', 'Heavy']" :selected="(array) request('body_type', [])" />
                                    <x-multi-select name="annual_income" label="Annual Income" :options="config('reference_data.annual_income_list', [])" :selected="(array) request('annual_income', [])" :searchable="true" />
                                    <x-multi-select name="diet" label="Eating Habit" :options="config('reference_data.eating_habits', [])" :selected="(array) request('diet', [])" />
                                    <x-multi-select name="drinking" label="Drinking Habit" :options="config('reference_data.drinking_habits', [])" :selected="(array) request('drinking', [])" />
                                    <x-multi-select name="smoking" label="Smoking Habit" :options="config('reference_data.smoking_habits', [])" :selected="(array) request('smoking', [])" />
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-8 pt-4 border-t border-gray-200">
                                <a href="{{ route('search.advance') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-center">Clear</a>
                                <button type="submit" class="w-full sm:w-auto px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── KEYWORD SEARCH TAB ── --}}
                <div x-show="activeTab === 'keyword'" x-cloak>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Keyword Search</h2>
                        <p class="text-sm text-gray-500 mb-6">Search profiles by name, profession, religion, or any keyword.</p>
                        <form method="GET" action="{{ route('search.results.public') }}" class="space-y-4">
                            <input type="hidden" name="search_type" value="keyword">
                            <div class="float-field">
                                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder=" " required minlength="3" maxlength="100">
                                <label>Enter Keyword (e.g. Doctor, Bangalore, Catholic)</label>
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                Search
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── SEARCH BY ID TAB ── --}}
                <div x-show="activeTab === 'byid'" x-cloak>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Search by Matrimony ID</h2>
                        <form method="GET" action="{{ route('search.results.public') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
                            <input type="hidden" name="search_type" value="byid">
                            <div class="float-field flex-1">
                                <input type="text" name="matri_id" value="{{ request('matri_id') }}" placeholder=" " required
                                    class="uppercase" maxlength="20">
                                <label>Enter Matrimony ID (e.g. {{ \App\Models\SiteSetting::getValue('profile_id_prefix', 'AM') }}100001)</label>
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors shrink-0">
                                Search
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
