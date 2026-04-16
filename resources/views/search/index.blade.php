<x-layouts.app title="Search">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        activeTab: '{{ $activeTab }}',
        religions: [],
        moreOpen: false,
        showProfileOpen: false,
        lifestyleOpen: false,
        hasReligion(r) { return this.religions.includes(r); }
    }">

        {{-- Breadcrumb --}}
        <p class="text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-(--color-primary)">My Home</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Search</span>
        </p>

        <div class="flex flex-col lg:flex-row gap-8">

            {{-- ══ LEFT NAV ══ --}}
            <div class="hidden lg:block lg:w-48 shrink-0">
                <div class="sticky top-24 space-y-1">
                    <h2 class="text-base font-semibold text-gray-900 mb-3">Partner Search</h2>
                    <button @click="activeTab = 'partner'"
                        :class="activeTab === 'partner' ? 'bg-(--color-primary) text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        Partner Search
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

            {{-- ══ RIGHT: CONTENT ══ --}}
            <div class="flex-1 min-w-0">

                {{-- ── PARTNER SEARCH TAB ── --}}
                <div x-show="activeTab === 'partner'" x-cloak>
                    <div class="bg-white rounded-lg border border-gray-200 shadow-xs p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Search by Preferences</h2>
                            @if($prefs)
                                <a href="{{ route('search.index', ['tab' => 'partner', 'load_prefs' => 1]) }}"
                                    class="text-sm text-(--color-primary) hover:underline font-medium">Load Partner Preferences</a>
                            @endif
                        </div>

                        <form method="GET" action="{{ route('search.index') }}">
                            <input type="hidden" name="search" value="1">
                            <input type="hidden" name="tab" value="partner">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {{-- Age Range --}}
                                <div class="float-field">
                                    <select name="age_from">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_from', $defaults['age_from']) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age From</label>
                                </div>
                                <div class="float-field">
                                    <select name="age_to">
                                        @for($i = 18; $i <= 70; $i++)
                                            <option value="{{ $i }}" {{ (int) request('age_to', $defaults['age_to']) === $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <label>Age To</label>
                                </div>

                                {{-- Height Range --}}
                                <div class="float-field">
                                    <select name="height_from">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_from', $defaults['height_from']) === $h ? 'selected' : '' }}>{{ $h }}</option>
                                        @endforeach
                                    </select>
                                    <label>Height From</label>
                                </div>
                                <div class="float-field">
                                    <select name="height_to">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.height_list', []) as $h)
                                            <option value="{{ $h }}" {{ request('height_to', $defaults['height_to']) === $h ? 'selected' : '' }}>{{ $h }}</option>
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

                            {{-- Religion with cascading --}}
                            <div class="mt-5">
                                <x-multi-select name="religion" label="Religion"
                                    :options="['Christian', 'Hindu', 'Muslim', 'Jain', 'Sikh', 'Buddhist', 'Other']"
                                    :selected="(array) request('religion', [])"
                                    emitTo="religions" />
                            </div>

                            {{-- Cascading: Christian --}}
                            <div x-show="hasReligion('Christian')" class="mt-5 space-y-5">
                                <x-multi-select name="denomination" label="Denomination"
                                    :options="config('reference_data.denomination_list', [])"
                                    :selected="(array) request('denomination', [])" :grouped="true" :searchable="true" />
                            </div>

                            {{-- Cascading: Hindu/Jain --}}
                            <div x-show="hasReligion('Hindu') || hasReligion('Jain')" class="mt-5 space-y-5">
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

                            {{-- Location --}}
                            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="float-field">
                                    <select name="working_country">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.country_list') as $group => $countries)
                                            <optgroup label="{{ $group }}">
                                                @foreach($countries as $c)
                                                    <option value="{{ $c }}" {{ request('working_country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <label>Working Country</label>
                                </div>
                                <div class="float-field">
                                    <select name="native_country">
                                        <option value="">Any</option>
                                        @foreach(config('reference_data.country_list') as $group => $countries)
                                            <optgroup label="{{ $group }}">
                                                @foreach($countries as $c)
                                                    <option value="{{ $c }}" {{ request('native_country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <label>Native Country</label>
                                </div>
                            </div>

                            {{-- Mother Tongue --}}
                            <div class="mt-5">
                                <x-multi-select name="mother_tongue" label="Mother Tongue"
                                    :options="config('reference_data.language_list', [])"
                                    :selected="(array) request('mother_tongue', [])" :searchable="true" />
                            </div>

                            {{-- ── Collapsible: Add More Criteria ── --}}
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
                                </div>
                            </div>

                            {{-- ── Collapsible: Show Profile ── --}}
                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <button type="button" @click="showProfileOpen = !showProfileOpen" class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                                    Show Profile
                                    <svg class="w-4 h-4 transition-transform" :class="showProfileOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="showProfileOpen" x-cloak class="mt-4 space-y-3">
                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="with_photo" value="1" {{ request('with_photo') ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                                        With Photo Only
                                    </label>
                                </div>
                            </div>

                            {{-- ── Collapsible: Lifestyle Habits ── --}}
                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <button type="button" @click="lifestyleOpen = !lifestyleOpen" class="flex items-center justify-between w-full text-sm font-semibold text-gray-700">
                                    Life Style Habits
                                    <svg class="w-4 h-4 transition-transform" :class="lifestyleOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="lifestyleOpen" x-cloak class="mt-4 space-y-5">
                                    <x-multi-select name="diet" label="Eating Habit" :options="config('reference_data.eating_habits', [])" :selected="(array) request('diet', [])" />
                                    <x-multi-select name="drinking" label="Drinking Habit" :options="config('reference_data.drinking_habits', [])" :selected="(array) request('drinking', [])" />
                                    <x-multi-select name="smoking" label="Smoking Habit" :options="config('reference_data.smoking_habits', [])" :selected="(array) request('smoking', [])" />
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-8 pt-4 border-t border-gray-200">
                                <a href="{{ route('search.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-center">Clear</a>
                                <button type="submit" class="w-full sm:w-auto px-8 py-2.5 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors">
                                    Search Partner
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
                        <form method="GET" action="{{ route('search.index') }}" class="space-y-4">
                            <input type="hidden" name="tab" value="keyword">
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
                        <form method="GET" action="{{ route('search.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4">
                            <input type="hidden" name="tab" value="byid">
                            <div class="float-field flex-1">
                                <input type="text" name="matri_id" value="{{ request('matri_id') }}" placeholder=" " required
                                    class="uppercase" maxlength="20">
                                <label>Enter Matrimony ID (e.g. {{ \App\Models\SiteSetting::getValue('profile_id_prefix', 'AM') }}100001)</label>
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg transition-colors shrink-0">
                                Search
                            </button>
                        </form>

                        @if(request('matri_id'))
                            <div class="mt-6 pt-6 border-t border-gray-100">
                                @if($idResult)
                                    <p class="text-sm text-gray-600 mb-4">Profile found:</p>
                                    <div class="max-w-xs">
                                        <x-profile-card :profile="$idResult" />
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">No profile found with ID "{{ request('matri_id') }}".</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
