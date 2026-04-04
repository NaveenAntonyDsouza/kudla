<x-layouts.registration title="Step 2 - Primary & Religious Info" :step="2">

    <h2 class="text-lg font-semibold text-gray-900 mb-6">Primary Information</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store2') }}" enctype="multipart/form-data" x-data="{
        religion: '{{ old('religion', $religiousInfo->religion ?? '') }}',
        maritalStatus: '{{ old('marital_status', $profile->marital_status ?? '') }}',
        physicalStatus: '{{ old('physical_status', $profile->physical_status ?? '') }}',
        communities: [],
        subCommunities: [],
        selectedCaste: '{{ old('caste', $religiousInfo->caste ?? '') }}',
        selectedSubCaste: '{{ old('sub_caste', $religiousInfo->sub_caste ?? '') }}',

        async fetchCommunities() {
            if (!this.religion || this.religion === 'Other' || this.religion === 'No Religion') {
                this.communities = [];
                this.subCommunities = [];
                this.selectedCaste = '';
                this.selectedSubCaste = '';
                return;
            }
            const response = await fetch(`/api/cascade/communities?religion=${encodeURIComponent(this.religion)}`);
            this.communities = await response.json();
            this.subCommunities = [];
            this.selectedSubCaste = '';
            if (this.selectedCaste) this.loadSubCommunities();
        },

        loadSubCommunities() {
            const community = this.communities.find(c => c.community_name === this.selectedCaste);
            this.subCommunities = community ? (community.sub_communities || []) : [];
            this.selectedSubCaste = '';
        },

        init() {
            if (this.religion) this.fetchCommunities();
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
                    @foreach(['Very Fair', 'Fair', 'Moderate Fair', 'Medium', 'Dark', 'Prefer Not to Say'] as $opt)
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
                    @foreach(['Slim', 'Athletic', 'Average', 'Heavy', 'Prefer Not to Say'] as $opt)
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
                    <option value="Normal" {{ old('physical_status', $profile->physical_status ?? '') === 'Normal' ? 'selected' : '' }}>Normal</option>
                    <option value="Differently Abled" {{ old('physical_status', $profile->physical_status ?? '') === 'Differently Abled' ? 'selected' : '' }}>Differently Abled</option>
                </select>
                <label for="physical_status">Physical Status <span class="text-red-500">*</span></label>
                @error('physical_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Differently Abled Details --}}
            <div x-show="physicalStatus === 'Differently Abled'" x-transition class="space-y-5 pl-4 border-l-2 border-(--color-primary)/30" x-data="{ daCategory: '{{ old('da_category', '') }}' }">
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
                    @foreach(['Unmarried', 'Divorcee', 'Awaiting Divorce', 'Widower', 'Annulled'] as $opt)
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

            {{-- Family Status --}}
            <div class="float-field">
                <select name="family_status" id="family_status" required>
                    <option value="">Select</option>
                    @foreach(['Lower Middle Class', 'Middle Class', 'Upper Middle Class', 'Rich'] as $opt)
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
                    <option value="Christian">Christian</option>
                    <option value="Hindu">Hindu</option>
                    <option value="Muslim">Muslim</option>
                    <option value="Jain">Jain</option>
                    <option value="No Religion">No Religion</option>
                    <option value="Other">Other</option>
                </select>
                <label for="religion">Religion <span class="text-red-500">*</span></label>
                @error('religion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ── Christian Fields ──────────────── --}}
            <template x-if="religion === 'Christian'">
                <div class="space-y-5" x-data="{ selectedDiocese: '{{ old('diocese', $religiousInfo->diocese ?? '') }}' }">
                    <div class="float-field">
                        <select name="denomination" id="denomination">
                            <option value="">Select</option>
                            @foreach(config('reference_data.denomination_list') as $group => $items)
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $denom)
                                        <option value="{{ $denom }}" {{ old('denomination', $religiousInfo->denomination ?? '') === $denom ? 'selected' : '' }}>{{ $denom }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <label for="denomination">Denomination <span class="text-red-500">*</span></label>
                        @error('denomination') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="float-field">
                        <select name="diocese" id="diocese" x-model="selectedDiocese">
                            <option value="">Select</option>
                            @foreach(config('reference_data.diocese_list') as $dio)
                                <option value="{{ $dio }}" {{ old('diocese', $religiousInfo->diocese ?? '') === $dio ? 'selected' : '' }}>{{ $dio }}</option>
                            @endforeach
                        </select>
                        <label for="diocese">Diocese</label>
                        @error('diocese') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="selectedDiocese === 'Other'" x-transition class="float-field">
                        <input type="text" name="diocese_name" id="diocese_name" value="{{ old('diocese_name', $religiousInfo->diocese_name ?? '') }}" placeholder=" ">
                        <label for="diocese_name">Diocese Name</label>
                        @error('diocese_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
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
                        <select name="caste" id="caste" x-model="selectedCaste" @change="loadSubCommunities()">
                            <option value="">Select</option>
                            <template x-for="community in communities" :key="community.id">
                                <option :value="community.community_name" x-text="community.community_name"
                                    :selected="community.community_name === selectedCaste"></option>
                            </template>
                        </select>
                        <label for="caste">Caste / Community</label>
                        @error('caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div x-show="subCommunities.length > 0" x-transition class="float-field">
                        <select name="sub_caste" id="sub_caste" x-model="selectedSubCaste">
                            <option value="">Select</option>
                            <template x-for="sub in subCommunities" :key="sub">
                                <option :value="sub" x-text="sub" :selected="sub === selectedSubCaste"></option>
                            </template>
                        </select>
                        <label for="sub_caste">Sub-Caste / Sub-Community</label>
                        @error('sub_caste') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
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
                            @foreach(['Digambar', 'Svetambara', 'Other'] as $opt)
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
                        <select name="muslim_sect" id="muslim_sect">
                            <option value="">Select</option>
                            @foreach(['Sunni', 'Shia', 'Ahmadiyya', 'Sufi', 'Other', 'Prefer Not to Say'] as $opt)
                                <option value="{{ $opt }}" {{ old('muslim_sect', $religiousInfo->muslim_sect ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <label for="muslim_sect">Sect</label>
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
                            @foreach(['Practicing', 'Moderately Practicing', 'Non-practicing', 'Prefer Not to Say'] as $opt)
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
