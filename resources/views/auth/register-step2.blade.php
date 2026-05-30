<x-layouts.registration title="Step 2 - Primary & Religious Info" :step="2">

    <h2 class="text-lg font-semibold text-gray-900 mb-6">Primary Information</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below:</p>
            <ul class="mt-1 text-xs text-red-500 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store2') }}" enctype="multipart/form-data" x-data="{
        religion: '{{ old('religion', $religiousInfo->religion ?? '') }}',
        maritalStatus: '{{ old('marital_status', $profile->marital_status ?? '') }}',
        physicalStatus: '{{ old('physical_status', $profile->physical_status ?? '') }}',
        communities: [],
        subCommunities: [],
        selectedCaste: '{{ old('caste', $religiousInfo->caste ?? '') }}',
        otherCasteName: '{{ old('other_caste_name', $religiousInfo->other_caste_name ?? '') }}',
        subCasteChoice: '',
        subCasteOther: '',
        savedSubCaste: '{{ old('sub_caste', $religiousInfo->sub_caste ?? '') }}',

        async fetchCommunities(preserve = false) {
            const keepCaste = this.selectedCaste;
            if (!this.religion || this.religion === 'Other' || this.religion === 'No Religion') {
                this.communities = []; this.subCommunities = [];
                this.selectedCaste = ''; this.resetSubCaste();
                return;
            }
            try {
                const res = await fetch('/api/cascade/communities?religion=' + encodeURIComponent(this.religion));
                this.communities = await res.json();
            } catch (e) { this.communities = []; }
            // Keep an existing caste even if it's no longer in the managed list.
            if (preserve && keepCaste && !this.communities.some(c => c.community_name === keepCaste)) {
                this.communities.push({ id: 'existing', community_name: keepCaste, sub_communities: [] });
            }
            this.selectedCaste = preserve ? keepCaste : '';
            this.loadSubCommunities(preserve);
        },

        loadSubCommunities(preserve = false) {
            const community = this.communities.find(c => c.community_name === this.selectedCaste);
            this.subCommunities = community ? (community.sub_communities || []) : [];
            if (preserve && this.savedSubCaste) {
                // A listed value selects it; an unlisted value routes to the
                // 'Other' free-text box (preserves old() on validation re-render).
                if (this.subCommunities.includes(this.savedSubCaste)) {
                    this.subCasteChoice = this.savedSubCaste; this.subCasteOther = '';
                } else {
                    this.subCasteChoice = '__other__'; this.subCasteOther = this.savedSubCaste;
                }
            } else {
                this.resetSubCaste();
            }
        },

        resetSubCaste() { this.subCasteChoice = ''; this.subCasteOther = ''; },

        init() {
            if (this.religion) this.fetchCommunities(true);
        }
    }">
        @csrf

        <div class="space-y-5">
            {{-- Height --}}
            <div class="float-field">
                <select name="height" id="height" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.height_list') as $h)
                        <option value="{{ $h }}" {{ old('height', $profile->height ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
                <label for="height">Height <span class="text-red-500">*</span></label>
                @error('height') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Complexion --}}
            <div class="float-field">
                <select name="complexion" id="complexion" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.complexion_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('complexion', $profile->complexion ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="complexion">Complexion <span class="text-red-500">*</span></label>
                @error('complexion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Body Type --}}
            <div class="float-field">
                <select name="body_type" id="body_type" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.body_type_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('body_type', $profile->body_type ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="body_type">Body Type <span class="text-red-500">*</span></label>
                @error('body_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Physical Status --}}
            <div class="float-field">
                <select name="physical_status" id="physical_status" x-model="physicalStatus" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.physical_status_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('physical_status', $profile->physical_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="physical_status">Physical Status <span class="text-red-500">*</span></label>
                @error('physical_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Differently Abled Details --}}
            <div x-show="physicalStatus === 'Differently Abled'" x-transition class="space-y-5 pl-4 border-l-2 border-(--color-primary)/30" x-data="{ daCategory: '{{ old('da_category', '') }}' }"
                 x-effect="if (physicalStatus !== 'Differently Abled') { daCategory = ''; $el.querySelectorAll('input, textarea').forEach(el => el.value = ''); }">
                <div class="float-field">
                    <select name="da_category" id="da_category" x-model="daCategory">
                        <option value="">Select</option>
                        @foreach(['Deaf & Dumb', 'Dwarfism', 'Hearing Impaired', 'Mentally Challenged', 'Physical Disability', 'Speech Impaired', 'Visually Challenged', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('da_category') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <label for="da_category">Category of Differently Abled <span class="text-red-500">*</span></label>
                    @error('da_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div x-show="daCategory === 'Other'" x-transition class="float-field">
                    <input type="text" name="da_category_other" id="da_category_other" value="{{ old('da_category_other') }}" maxlength="50" placeholder=" ">
                    <label for="da_category_other">Specify Differently Abled <span class="text-red-500">*</span></label>
                    @error('da_category_other') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="float-field">
                    <textarea name="da_description" id="da_description" rows="3" maxlength="500" placeholder=" "
                        class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('da_description') }}</textarea>
                    <label for="da_description">Describe Differently Abled <span class="text-red-500">*</span></label>
                    @error('da_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Marital Status --}}
            <div class="float-field">
                <select name="marital_status" id="marital_status" x-model="maritalStatus" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.marital_status_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('marital_status', $profile->marital_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="marital_status">Marital Status <span class="text-red-500">*</span></label>
                @error('marital_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Children (when not Unmarried) --}}
            <template x-if="maritalStatus && maritalStatus !== 'Unmarried'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="float-field">
                        <input type="number" name="children_with_me" id="children_with_me" value="{{ old('children_with_me', $profile->children_with_me ?? 0) }}" min="0" placeholder=" ">
                        <label for="children_with_me">Children with me</label>
                        @error('children_with_me') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="float-field">
                        <input type="number" name="children_not_with_me" id="children_not_with_me" value="{{ old('children_not_with_me', $profile->children_not_with_me ?? 0) }}" min="0" placeholder=" ">
                        <label for="children_not_with_me">Children not with me</label>
                        @error('children_not_with_me') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </template>

            {{-- Mother Tongue — core match field, so required at signup. --}}
            <div class="float-field">
                <select name="mother_tongue" id="mother_tongue" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.language_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('mother_tongue', $profile->mother_tongue ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="mother_tongue">Mother Tongue <span class="text-red-500">*</span></label>
                @error('mother_tongue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Other languages known — optional, multi-select. --}}
            <div>
                <x-multi-select name="languages_known" label="Other Languages Known (optional)"
                    :options="config('reference_data.language_list', [])"
                    :selected="$profile?->lifestyleInfo?->languages_known ?? []"
                    :searchable="true" :showAny="false" />
                @error('languages_known') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Family Status --}}
            <div class="float-field">
                <select name="family_status" id="family_status" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.family_status_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('family_status', $familyDetail?->family_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="family_status">Family Status <span class="text-red-500">*</span></label>
                @error('family_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Religious Information ──────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mt-8 mb-6">Religious Information</h2>

        <div class="space-y-5">
            {{-- Religion --}}
            <div class="float-field">
                <select name="religion" id="religion" x-model="religion" @change="fetchCommunities()" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.religion_list', []) as $opt)
                        <option value="{{ $opt }}" {{ old('religion', $religiousInfo->religion ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="religion">Religion <span class="text-red-500">*</span></label>
                @error('religion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ── Christian Fields ──────────────── --}}
            <template x-if="religion === 'Christian'">
                <div class="space-y-5" x-data="{
                    selectedDenomination: '{{ old('denomination', $religiousInfo->denomination ?? '') }}',
                    otherDenominationName: '{{ old('other_denomination_name', $religiousInfo->other_denomination_name ?? '') }}',
                    dioceses: [],
                    dioceseChoice: '',
                    dioceseOther: '',
                    savedDiocese: '{{ old('diocese', $religiousInfo->diocese ?? '') }}',
                    savedDioceseName: '{{ old('diocese_name', $religiousInfo->diocese_name ?? '') }}',

                    async fetchDioceses(preserve = false) {
                        if (!this.selectedDenomination) {
                            this.dioceses = [];
                            if (!preserve) { this.dioceseChoice = ''; this.dioceseOther = ''; }
                            return;
                        }
                        try {
                            const res = await fetch('/api/cascade/dioceses?denomination=' + encodeURIComponent(this.selectedDenomination));
                            this.dioceses = await res.json();
                        } catch (e) { this.dioceses = []; }
                        if (preserve) {
                            const saved = this.savedDiocese;
                            if (saved && saved !== 'Other' && this.dioceses.includes(saved)) {
                                this.dioceseChoice = saved; this.dioceseOther = '';
                            } else if (saved === 'Other' || this.savedDioceseName || saved) {
                                // 'Other', or a saved diocese outside this rite's list → keep via Other
                                this.dioceseChoice = '__other__';
                                this.dioceseOther = this.savedDioceseName || (saved !== 'Other' ? saved : '');
                            } else {
                                this.dioceseChoice = ''; this.dioceseOther = '';
                            }
                        } else {
                            this.dioceseChoice = ''; this.dioceseOther = '';
                        }
                    },

                    init() { if (this.selectedDenomination) this.fetchDioceses(true); }
                }">
                    <div class="float-field">
                        <select name="denomination" id="denomination" x-model="selectedDenomination" @change="fetchDioceses()" required>
                            <option value="">Select</option>
                            @foreach(config('reference_data.denomination_list') as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $denom)
                                        <option value="{{ $denom }}">{{ $denom }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <label for="denomination">Denomination <span class="text-red-500">*</span></label>
                        @error('denomination') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    {{-- Specify the denomination when "Other" is picked (covers
                         rare Eastern Catholic rites or breakaway groups under
                         either Catholic or Non-Catholic). --}}
                    <div class="float-field" x-show="selectedDenomination === 'Other'" x-transition>
                        <input type="text" name="other_denomination_name" id="other_denomination_name" x-model="otherDenominationName" maxlength="100" placeholder=" ">
                        <label for="other_denomination_name">Specify Denomination <span class="text-red-500">*</span></label>
                        @error('other_denomination_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    {{-- Diocese: filtered to the denomination's rite via
                         /api/cascade/dioceses. "Other (not listed)" reveals a
                         free-text box — also the path for Non-Catholic
                         denominations, whose dioceses aren't in this list. The
                         <select> is UI-only; hidden inputs submit diocese +
                         diocese_name. --}}
                    <template x-if="selectedDenomination">
                        <div class="contents">
                            <div class="float-field">
                                <select id="diocese" x-model="dioceseChoice">
                                    <option value="">Select</option>
                                    <template x-for="d in dioceses" :key="d">
                                        <option :value="d" x-text="d"></option>
                                    </template>
                                    <option value="__other__">Other (not listed)</option>
                                </select>
                                <label for="diocese">Diocese</label>
                            </div>
                            <div class="float-field" x-show="dioceseChoice === '__other__'" x-transition>
                                <input type="text" id="diocese_name" x-model="dioceseOther" maxlength="100" placeholder=" ">
                                <label for="diocese_name">Diocese Name</label>
                            </div>
                            <input type="hidden" name="diocese" :value="dioceseChoice === '__other__' ? 'Other' : dioceseChoice">
                            <input type="hidden" name="diocese_name" :value="dioceseChoice === '__other__' ? dioceseOther : ''">
                        </div>
                    </template>
                    <div class="float-field">
                        <textarea name="parish_name_place" id="parish_name_place" rows="3" placeholder=" "
                            class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('parish_name_place', $religiousInfo->parish_name_place ?? '') }}</textarea>
                        <label for="parish_name_place">Parish Name and Place</label>
                        @error('parish_name_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </template>

            {{-- ── Hindu / Jain Fields ──────────── --}}
            <template x-if="religion === 'Hindu' || religion === 'Jain'">
                <div class="space-y-5">
                    <div class="float-field">
                        <select name="caste" id="caste" x-model="selectedCaste" @change="loadSubCommunities()" required>
                            <option value="">Select</option>
                            <template x-for="community in communities" :key="community.id">
                                <option :value="community.community_name" x-text="community.community_name"
                                    :selected="community.community_name === selectedCaste"></option>
                            </template>
                        </select>
                        <label for="caste">Caste / Community <span class="text-red-500">*</span></label>
                        @error('caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    {{-- Specify the caste when "Other (not listed)" / "Other"
                         is picked, so we capture the actual community rather
                         than just "they didn't fit the list". --}}
                    <div class="float-field" x-show="selectedCaste === 'Other (not listed)' || selectedCaste === 'Other'" x-transition>
                        <input type="text" name="other_caste_name" id="other_caste_name" x-model="otherCasteName" maxlength="100" placeholder=" ">
                        <label for="other_caste_name">Specify Caste / Community</label>
                        @error('other_caste_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    {{-- Sub-Caste: from the chosen community's sub-communities,
                         plus an "Other (not listed)" escape hatch. The <select>
                         is UI-only; the hidden input submits the real value
                         (typed text when "Other", else the picked option). --}}
                    <template x-if="selectedCaste">
                        <div class="space-y-5">
                            <div class="float-field">
                                <select id="sub_caste" x-model="subCasteChoice">
                                    <option value="">Select</option>
                                    <template x-for="sub in subCommunities" :key="sub">
                                        <option :value="sub" x-text="sub"></option>
                                    </template>
                                    <option value="__other__">Other (not listed)</option>
                                </select>
                                <label for="sub_caste">Sub-Caste / Sub-Community</label>
                            </div>
                            <div class="float-field" x-show="subCasteChoice === '__other__'" x-transition>
                                <input type="text" id="sub_caste_other" x-model="subCasteOther" maxlength="50" placeholder=" ">
                                <label for="sub_caste_other">Enter Sub-Caste / Sub-Community</label>
                            </div>
                            <input type="hidden" name="sub_caste" :value="subCasteChoice === '__other__' ? subCasteOther : subCasteChoice">
                            @error('sub_caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </template>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="float-field">
                            <input type="time" name="time_of_birth" id="time_of_birth" value="{{ old('time_of_birth', $religiousInfo->time_of_birth ?? '') }}" placeholder=" ">
                            <label for="time_of_birth">Time of Birth</label>
                        </div>
                        <div class="float-field">
                            <input type="text" name="place_of_birth" id="place_of_birth" value="{{ old('place_of_birth', $religiousInfo->place_of_birth ?? '') }}" placeholder=" ">
                            <label for="place_of_birth">Place of Birth</label>
                        </div>
                    </div>
                    <div class="float-field">
                        <select name="rashi" id="rashi">
                            <option value="">Select</option>
                            @foreach(config('reference_data.rasi_list') as $sign)
                                <option value="{{ $sign }}" {{ old('rashi', $religiousInfo->rashi ?? '') === $sign ? 'selected' : '' }}>{{ $sign }}</option>
                            @endforeach
                        </select>
                        <label for="rashi">Rasi (Zodiac)</label>
                    </div>
                    <div class="float-field">
                        <select name="nakshatra" id="nakshatra">
                            <option value="">Select</option>
                            @foreach(config('reference_data.nakshatra_list') as $star)
                                <option value="{{ $star }}" {{ old('nakshatra', $religiousInfo->nakshatra ?? '') === $star ? 'selected' : '' }}>{{ $star }}</option>
                            @endforeach
                        </select>
                        <label for="nakshatra">Nakshatra (Star)</label>
                    </div>
                    <div class="float-field">
                        <select name="gotra" id="gotra">
                            <option value="">Select</option>
                            @foreach(config('reference_data.gothram_list') as $opt)
                                <option value="{{ $opt }}" {{ old('gotra', $religiousInfo->gotra ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="gotra">Gothram</label>
                    </div>
                    <div class="float-field">
                        <select name="manglik" id="manglik">
                            <option value="">Select</option>
                            @foreach(['Yes', 'No', "Don't Know"] as $opt)
                                <option value="{{ $opt }}" {{ old('manglik', $religiousInfo->dosh ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="manglik">Manglik / Chovva Dosham</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jathakam / Horoscope</label>
                        @if($religiousInfo?->jathakam_upload_url)
                            <div class="mb-2 flex items-center gap-3 p-2 bg-green-50 border border-green-200 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm text-green-700">Horoscope uploaded</span>
                                <a href="{{ Storage::disk('public')->url($religiousInfo->jathakam_upload_url) }}" target="_blank" class="text-sm text-(--color-primary) hover:underline font-medium ml-auto">View</a>
                            </div>
                            <p class="text-xs text-gray-500 mb-1">Upload a new file to replace the existing one:</p>
                        @endif
                        <input type="file" name="jathakam" id="jathakam" accept=".jpg,.jpeg,.png,.pdf"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-(--color-primary)/10 file:text-(--color-primary)">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or PDF (max 2MB)</p>
                        @error('jathakam') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    {{-- Jain-specific --}}
                    <div x-show="religion === 'Jain'" x-transition class="float-field">
                        <select name="jain_sect" id="jain_sect">
                            <option value="">Select</option>
                            @foreach(config('reference_data.jain_sect_list', []) as $opt)
                                <option value="{{ $opt }}" {{ old('jain_sect', $religiousInfo->jain_sect ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="jain_sect">Jain Sect</label>
                    </div>
                </div>
            </template>

            {{-- ── Muslim Fields ─────────────────── --}}
            <template x-if="religion === 'Muslim'">
                <div class="space-y-5">
                    <div class="float-field">
                        <select name="muslim_sect" id="muslim_sect" required>
                            <option value="">Select</option>
                            @foreach(config('reference_data.muslim_sect_list', []) as $opt)
                                <option value="{{ $opt }}" {{ old('muslim_sect', $religiousInfo->muslim_sect ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="muslim_sect">Sect <span class="text-red-500">*</span></label>
                    </div>
                    <div class="float-field">
                        <select name="muslim_community" id="muslim_community">
                            <option value="">Select</option>
                            @foreach(config('reference_data.jamath_list') as $opt)
                                <option value="{{ $opt }}" {{ old('muslim_community', $religiousInfo->muslim_community ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="muslim_community">Community / Jamath</label>
                    </div>
                    <div class="float-field">
                        <select name="religious_observance" id="religious_observance">
                            <option value="">Select</option>
                            @foreach(config('reference_data.religious_observance_list', []) as $opt)
                                <option value="{{ $opt }}" {{ old('religious_observance', $religiousInfo->religious_observance ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="religious_observance">Religious Observance</label>
                    </div>
                </div>
            </template>

            {{-- ── Other Religion ────────────────── --}}
            <div x-show="religion === 'Other'" x-transition class="float-field">
                <input type="text" name="other_religion_name" id="other_religion_name" value="{{ old('other_religion_name', $religiousInfo->other_religion_name ?? '') }}" placeholder=" ">
                <label for="other_religion_name">Specify Religion <span class="text-red-500">*</span></label>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('register') }}"
                class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Next
            </button>
        </div>
    </form>
</x-layouts.registration>
