<x-layouts.onboarding title="Partner Preferences" :step="3" :completionPct="$completionPct">

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif

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

    <form method="POST" action="{{ route('onboarding.storePreferences') }}" @submit="submitting = true" x-data="{
        submitting: false,
        religions: {{ Js::from(old('religions', $defaultReligions)) }},
        maritalStatus: {{ Js::from(old('marital_status', $pref?->marital_status ?? [])) }},
        physicalStatus: {{ Js::from(old('physical_status', $pref?->physical_status ?? [])) }},

        hasReligion(r) { return this.religions.includes(r) && !this.religions.includes('Any'); },
        hasNonUnmarried() {
            if (this.maritalStatus.length === 0) return false;
            if (this.maritalStatus.includes('Any')) return true;
            return !this.maritalStatus.every(s => s === 'Unmarried');
        },
        hasNonNormal() {
            if (this.physicalStatus.length === 0) return false;
            if (this.physicalStatus.includes('Any')) return true;
            return !this.physicalStatus.every(s => s === 'Normal');
        }
    }" @multiselect-change.window="
        if ($event.detail.name === 'religions') religions = $event.detail.value;
        if ($event.detail.name === 'marital_status') maritalStatus = $event.detail.value;
        if ($event.detail.name === 'physical_status') physicalStatus = $event.detail.value;
    ">
        @csrf

        {{-- ── Primary Requirements ──────────────────────────── --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Primary Requirements</h2>
            <a href="{{ route('onboarding.lifestyle') }}" class="text-sm text-(--color-primary) hover:underline font-medium">Skip for now &rarr;</a>
        </div>

        <div class="space-y-5 mb-10">
            {{-- Age Range --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="float-field">
                    <select name="age_from" id="age_from">
                        <option value="">Select</option>
                        @for($i = 18; $i <= 70; $i++)
                            <option value="{{ $i }}" {{ old('age_from', $pref?->age_from ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <label for="age_from">Min Age</label>
                </div>
                <div class="float-field">
                    <select name="age_to" id="age_to">
                        <option value="">Select</option>
                        @for($i = 18; $i <= 70; $i++)
                            <option value="{{ $i }}" {{ old('age_to', $pref?->age_to ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <label for="age_to">Max Age</label>
                </div>
            </div>

            {{-- Height Range --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="float-field">
                    <select name="height_from" id="height_from">
                        <option value="">Select</option>
                        @foreach(config('reference_data.height_list') as $h)
                            <option value="{{ $h }}" {{ old('height_from', $pref?->height_from_cm ?? '') == $h ? 'selected' : '' }}>{{ $h }}</option>
                        @endforeach
                    </select>
                    <label for="height_from">Min Height</label>
                </div>
                <div class="float-field">
                    <select name="height_to" id="height_to">
                        <option value="">Select</option>
                        @foreach(config('reference_data.height_list') as $h)
                            <option value="{{ $h }}" {{ old('height_to', $pref?->height_to_cm ?? '') == $h ? 'selected' : '' }}>{{ $h }}</option>
                        @endforeach
                    </select>
                    <label for="height_to">Max Height</label>
                </div>
            </div>

            {{-- Complexion --}}
            <x-multi-select name="complexion" label="Complexion"
                :options="['Very Fair', 'Fair', 'Moderate Fair', 'Medium', 'Dark', 'Prefer Not to Say']"
                :selected="$pref?->complexion ?? []" />

            {{-- Body Type --}}
            <x-multi-select name="body_type" label="Body Type"
                :options="['Slim', 'Athletic', 'Average', 'Heavy', 'Prefer Not to Say']"
                :selected="$pref?->body_type ?? []" />

            {{-- Marital Status --}}
            <x-multi-select name="marital_status" label="Marital Status"
                :options="['Unmarried', 'Divorcee', 'Awaiting Divorce', 'Widower', 'Annulled']"
                :selected="$pref?->marital_status ?? []" :emitTo="true" />

            {{-- Children Status (conditional - inline checkboxes matching Chavara) --}}
            <div x-show="hasNonUnmarried()" x-transition
                 x-effect="if (!hasNonUnmarried()) { $el.querySelectorAll('input[type=checkbox]').forEach(el => el.checked = false); }">
                <label class="block text-xs font-medium text-gray-500 mb-2">Children Status</label>
                <div class="flex flex-wrap gap-4">
                    @foreach(['Any', 'Having Children', 'No Children'] as $opt)
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="children_status[]" value="{{ $opt }}"
                                {{ in_array($opt, old('children_status', $pref?->children_status ?? [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                            {{ $opt }}
                        </label>
                    @endforeach
                </div>
                @error('children_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Physical Status --}}
            <x-multi-select name="physical_status" label="Physical Status"
                :options="['Normal', 'Differently Abled']"
                :selected="$pref?->physical_status ?? []" :emitTo="true" />

            {{-- DA Category (conditional) --}}
            <div x-show="hasNonNormal()" x-transition
                 x-effect="if (!hasNonNormal()) { $dispatch('multiselect-clear', { name: 'da_category' }); }">
                <x-multi-select name="da_category" label="Category of Differently Abled"
                    :options="['Deaf & Dumb', 'Dwarfism', 'Hearing Impaired', 'Mentally Challenged', 'Physical Disability', 'Speech Impaired', 'Visually Challenged', 'Other']"
                    :selected="$pref?->da_category ?? []" />
            </div>

            {{-- Family Status --}}
            <x-multi-select name="family_status" label="Family Status"
                :options="['Lower Middle Class', 'Middle Class', 'Upper Middle Class', 'Rich']"
                :selected="$pref?->family_status ?? []" />
        </div>

        {{-- ── Religious Requirements ────────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Religious Requirements</h2>

        <div class="space-y-5 mb-10">
            {{-- Religion --}}
            <x-multi-select name="religions" label="Religion"
                :options="['Christian', 'Hindu', 'Muslim', 'Jain', 'No Religion', 'Other']"
                :selected="$defaultReligions" :emitTo="true" />

            {{-- Christian: Denomination --}}
            <div x-show="hasReligion('Christian')" x-transition>
                <x-multi-select name="denomination" label="Denomination"
                    :options="config('reference_data.denomination_list')" :grouped="true" :searchable="true"
                    :selected="$pref?->denomination ?? []" />
            </div>

            {{-- Christian: Diocese --}}
            <div x-show="hasReligion('Christian')" x-transition>
                <x-multi-select name="diocese" label="Diocese"
                    :options="config('reference_data.diocese_list')" :searchable="true"
                    :selected="$pref?->diocese ?? []" />
            </div>

            {{-- Hindu/Jain: Caste --}}
            <div x-show="hasReligion('Hindu') || hasReligion('Jain')" x-transition>
                <x-multi-select name="caste" label="Caste / Community"
                    :options="\App\Models\Community::getCasteList()" :searchable="true"
                    :selected="$pref?->caste ?? []" />
            </div>

            {{-- Hindu/Jain: Sub-Caste --}}
            <div x-show="hasReligion('Hindu') || hasReligion('Jain')" x-transition>
                <x-multi-select name="sub_caste" label="Sub-Caste / Sub-Community"
                    :options="\App\Models\Community::getSubCasteList()" :searchable="true"
                    :selected="$pref?->sub_caste ?? []" />
            </div>

            {{-- Hindu: Manglik --}}
            <div x-show="hasReligion('Hindu')" x-transition>
                <x-multi-select name="manglik" label="Manglik / Chovva Dosham"
                    :options="['Yes', 'No', 'Don\'t Know']"
                    :selected="$pref?->manglik ?? []" />
            </div>

            {{-- Muslim: Sect --}}
            <div x-show="hasReligion('Muslim')" x-transition>
                <x-multi-select name="muslim_sect" label="Sect"
                    :options="['Sunni', 'Shia', 'Ahmadiyya', 'Sufi', 'Other', 'Prefer Not to Say']"
                    :selected="$pref?->muslim_sect ?? []" />
            </div>

            {{-- Muslim: Community --}}
            <div x-show="hasReligion('Muslim')" x-transition>
                <x-multi-select name="muslim_community" label="Community / Jamath"
                    :options="config('reference_data.jamath_list')" :searchable="true"
                    :selected="$pref?->muslim_community ?? []" />
            </div>

            {{-- Jain: Sect --}}
            <div x-show="hasReligion('Jain')" x-transition>
                <x-multi-select name="jain_sect" label="Jain Sect"
                    :options="['Digambar', 'Svetambara', 'Other']"
                    :selected="$pref?->jain_sect ?? []" />
            </div>

            {{-- Mother Tongue --}}
            <x-multi-select name="mother_tongues" label="Mother Tongue"
                :options="config('reference_data.language_list')" :searchable="true"
                :selected="$pref?->mother_tongues ?? []" />

            {{-- Partner Should Know Languages --}}
            <x-multi-select name="languages_known" label="Partner Should Know Languages"
                :options="config('reference_data.language_list')" :searchable="true"
                :selected="$pref?->languages_known ?? []" />
        </div>

        {{-- ── Education & Professional Requirements ─────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Education & Professional Requirements</h2>

        <div class="space-y-5 mb-10">
            {{-- Education Level --}}
            <x-multi-select name="education_levels" label="Education Level"
                :options="['High School', 'Diploma', 'Bachelor\'s', 'Master\'s', 'PhD', 'PG Diploma']"
                :selected="$pref?->education_levels ?? []" />

            {{-- Educational Qualifications (grouped, searchable) --}}
            <x-multi-select name="educational_qualifications" label="Educational Qualifications"
                :options="config('reference_data.educational_qualifications_list')" :grouped="true" :searchable="true"
                :selected="$pref?->educational_qualifications ?? []" />

            {{-- Occupation Category (grouped, searchable) --}}
            <x-multi-select name="occupations" label="Occupation"
                :options="config('reference_data.occupation_category_list')" :grouped="true" :searchable="true"
                :selected="$pref?->occupations ?? []" />

            {{-- Employment Status --}}
            <x-multi-select name="employment_status" label="Employment Category"
                :options="['Central Govt.', 'Entrepreneurship', 'Govt.', 'MNC', 'Others', 'Overseas', 'Own Business', 'Private', 'Public Limited', 'Semi Govt.']"
                :selected="$pref?->employment_status ?? []" />

            {{-- Annual Income --}}
            <x-multi-select name="income_range" label="Annual Income"
                :options="config('reference_data.annual_income_list')" :searchable="true"
                :selected="$pref?->income_range ?? []" />

            {{-- Working Country --}}
            <x-multi-select name="working_countries" label="Working Country"
                :options="config('reference_data.country_list')" :grouped="true" :searchable="true"
                :selected="$pref?->working_countries ?? []" />
        </div>

        {{-- ── Location Requirements ─────────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Location Requirements</h2>

        <div class="space-y-5 mb-10">
            {{-- Native Country --}}
            <x-multi-select name="native_countries" label="Native Country"
                :options="config('reference_data.country_list')" :grouped="true" :searchable="true"
                :selected="$pref?->native_countries ?? []" />

            {{-- Expectations --}}
            <div class="float-field" x-data="{ count: {{ mb_strlen(old('about_partner', $pref?->about_partner ?? '')) }} }">
                <textarea name="about_partner" id="about_partner" rows="4" maxlength="5000" placeholder=" "
                    @input="count = $el.value.length"
                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('about_partner', $pref?->about_partner ?? '') }}</textarea>
                <label for="about_partner">Expectations about the partner in detail</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 5000 Chars.)</p>
                @error('about_partner') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-6 border-t border-gray-200">
            <a href="{{ route('onboarding.step2') }}"
                class="w-full sm:w-auto text-center border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('onboarding.lifestyle') }}" class="text-sm text-(--color-primary) hover:underline font-medium order-2 sm:order-1">Skip for now</a>
                <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                    class="w-full sm:w-auto bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors order-1 sm:order-2">
                    <span x-show="!submitting">Save</span>
                    <span x-show="submitting" x-cloak>Please wait...</span>
                </button>
            </div>
        </div>
    </form>
</x-layouts.onboarding>
