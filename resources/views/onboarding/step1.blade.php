<x-layouts.onboarding title="Additional Info - Step 1" :step="1" :completionPct="$completionPct">

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('onboarding.store1') }}" @submit="submitting = true" x-data="{
        submitting: false,
        languagesKnown: {{ Js::from(old('languages_known', $lifestyleInfo?->languages_known ?? [])) }}
    }">
        @csrf

        {{-- ── Additional Primary Information ────────────────────── --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Additional Primary Information</h2>
        </div>

        <div class="space-y-5 mb-10">
            {{-- Weight --}}
            <div class="float-field">
                <select name="weight_kg" id="weight_kg">
                    <option value="">Select</option>
                    @foreach(config('reference_data.weight_list') as $w)
                            <option value="{{ $w }}" {{ old('weight_kg', $profile?->weight_kg ?? '') === $w ? 'selected' : '' }}>{{ $w }}</option>
                    @endforeach
                </select>
                <label for="weight_kg">Weight</label>
                @error('weight_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Blood Group --}}
            <div class="float-field">
                <select name="blood_group" id="blood_group">
                    <option value="">Select</option>
                    @foreach(['A+ve', 'A-ve', 'B+ve', 'B-ve', 'AB+ve', 'AB-ve', 'O+ve', 'O-ve', 'Prefer Not to Say'] as $bg)
                        <option value="{{ $bg }}" {{ old('blood_group', $profile?->blood_group ?? '') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                    @endforeach
                </select>
                <label for="blood_group">Blood Group</label>
                @error('blood_group') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Mother Tongue --}}
            <div class="float-field">
                <select name="mother_tongue" id="mother_tongue">
                    <option value="">Select</option>
                    @foreach(config('reference_data.language_list') as $lang)
                        <option value="{{ $lang }}" {{ old('mother_tongue', $profile?->mother_tongue ?? '') === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                    @endforeach
                </select>
                <label for="mother_tongue">Mother Tongue</label>
                @error('mother_tongue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Languages Known --}}
            <x-multi-select name="languages_known" label="Languages Known"
                :options="config('reference_data.language_list')" :searchable="true"
                :selected="$lifestyleInfo?->languages_known ?? []" :showAny="false" />

            {{-- About the Candidate --}}
            <div class="float-field" x-data="{ count: {{ mb_strlen(old('about_me', $profile?->about_me ?? '')) }} }">
                <textarea name="about_me" id="about_me" rows="4" maxlength="5000" placeholder=" "
                    @input="count = $el.value.length"
                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('about_me', $profile?->about_me ?? '') }}</textarea>
                <label for="about_me">About the Candidate</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 5000 Chars.)</p>
                @error('about_me') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Additional Education & Professional Information ──── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Additional Education & Professional Information</h2>

        <div class="space-y-5 mb-10">
            {{-- Show existing read-only values --}}
            @if($educationDetail?->highest_education)
                <div class="float-field">
                    <input type="text" value="{{ $educationDetail->highest_education }}" readonly class="bg-gray-50" placeholder=" ">
                    <label>Educational Qualifications</label>
                </div>
            @endif

            {{-- Education in Detail --}}
            <div class="float-field" x-data="{ count: {{ mb_strlen(old('education_detail', $educationDetail?->education_detail ?? '')) }} }">
                <input type="text" name="education_detail" id="education_detail" value="{{ old('education_detail', $educationDetail?->education_detail ?? '') }}" maxlength="200" placeholder=" " @input="count = $el.value.length">
                <label for="education_detail">Education in Detail</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 200 Chars.)</p>
                @error('education_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if($educationDetail?->occupation)
                <div class="float-field">
                    <input type="text" value="{{ $educationDetail->occupation }}" readonly class="bg-gray-50" placeholder=" ">
                    <label>Occupation Category</label>
                </div>
            @endif

            {{-- Occupation in Detail --}}
            <div class="float-field" x-data="{ count: {{ mb_strlen(old('occupation_detail', $educationDetail?->occupation_detail ?? '')) }} }">
                <input type="text" name="occupation_detail" id="occupation_detail" value="{{ old('occupation_detail', $educationDetail?->occupation_detail ?? '') }}" maxlength="200" placeholder=" " @input="count = $el.value.length">
                <label for="occupation_detail">Occupation in Detail</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 200 Chars.)</p>
                @error('occupation_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Organization Name --}}
            <div class="float-field">
                <input type="text" name="employer_name" id="employer_name" value="{{ old('employer_name', $educationDetail?->employer_name ?? '') }}" maxlength="100" placeholder=" ">
                <label for="employer_name">Organization Name</label>
                @error('employer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Additional Family Information ──────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Additional Family Information</h2>

        <div class="space-y-5 mb-10">
            <div class="float-field">
                <input type="text" name="father_name" id="father_name" value="{{ old('father_name', $familyDetail?->father_name ?? '') }}" placeholder=" ">
                <label for="father_name">Father's Name</label>
                @error('father_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="father_house_name" id="father_house_name" value="{{ old('father_house_name', $familyDetail?->father_house_name ?? '') }}" placeholder=" ">
                <label for="father_house_name">Father's Family Name / Surname</label>
                @error('father_house_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="father_native_place" id="father_native_place" value="{{ old('father_native_place', $familyDetail?->father_native_place ?? '') }}" placeholder=" ">
                <label for="father_native_place">Father's Native Place</label>
                @error('father_native_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="father_occupation" id="father_occupation" value="{{ old('father_occupation', $familyDetail?->father_occupation ?? '') }}" placeholder=" ">
                <label for="father_occupation">Father's Occupation</label>
                @error('father_occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name', $familyDetail?->mother_name ?? '') }}" placeholder=" ">
                <label for="mother_name">Mother's Name</label>
                @error('mother_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="mother_house_name" id="mother_house_name" value="{{ old('mother_house_name', $familyDetail?->mother_house_name ?? '') }}" placeholder=" ">
                <label for="mother_house_name">Mother's Maiden Family Name</label>
                @error('mother_house_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="mother_native_place" id="mother_native_place" value="{{ old('mother_native_place', $familyDetail?->mother_native_place ?? '') }}" placeholder=" ">
                <label for="mother_native_place">Mother's Native Place</label>
                @error('mother_native_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="mother_occupation" id="mother_occupation" value="{{ old('mother_occupation', $familyDetail?->mother_occupation ?? '') }}" placeholder=" ">
                <label for="mother_occupation">Mother's Occupation</label>
                @error('mother_occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field" x-data="{ count: {{ mb_strlen(old('candidate_asset_details', $familyDetail?->candidate_asset_details ?? '')) }} }">
                <textarea name="candidate_asset_details" id="candidate_asset_details" rows="3" maxlength="500" placeholder=" "
                    @input="count = $el.value.length"
                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('candidate_asset_details', $familyDetail?->candidate_asset_details ?? '') }}</textarea>
                <label for="candidate_asset_details">Candidate's Asset Details</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 500 Chars.)</p>
                @error('candidate_asset_details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field" x-data="{ count: {{ mb_strlen(old('about_candidate_family', $familyDetail?->about_candidate_family ?? '')) }} }">
                <textarea name="about_candidate_family" id="about_candidate_family" rows="4" maxlength="5000" placeholder=" "
                    @input="count = $el.value.length"
                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('about_candidate_family', $familyDetail?->about_candidate_family ?? '') }}</textarea>
                <label for="about_candidate_family">About Candidate's Family</label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 5000 Chars.)</p>
                @error('about_candidate_family') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Sibling Details ────────────────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Sibling Details</h2>

        <div class="space-y-6 mb-10">
            {{-- Brothers --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">No. of Brothers</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="float-field">
                        <input type="number" name="brothers_married" id="brothers_married" value="{{ old('brothers_married', $familyDetail?->brothers_married ?? 0) }}" min="0" placeholder=" ">
                        <label for="brothers_married">Married</label>
                    </div>
                    <div class="float-field">
                        <input type="number" name="brothers_unmarried" id="brothers_unmarried" value="{{ old('brothers_unmarried', $familyDetail?->brothers_unmarried ?? 0) }}" min="0" placeholder=" ">
                        <label for="brothers_unmarried">Unmarried</label>
                    </div>
                    <div class="float-field">
                        <input type="number" name="brothers_priest" id="brothers_priest" value="{{ old('brothers_priest', $familyDetail?->brothers_priest ?? 0) }}" min="0" placeholder=" ">
                        <label for="brothers_priest">Priest</label>
                    </div>
                </div>
            </div>

            {{-- Sisters --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">No. of Sisters</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="float-field">
                        <input type="number" name="sisters_married" id="sisters_married" value="{{ old('sisters_married', $familyDetail?->sisters_married ?? 0) }}" min="0" placeholder=" ">
                        <label for="sisters_married">Married</label>
                    </div>
                    <div class="float-field">
                        <input type="number" name="sisters_unmarried" id="sisters_unmarried" value="{{ old('sisters_unmarried', $familyDetail?->sisters_unmarried ?? 0) }}" min="0" placeholder=" ">
                        <label for="sisters_unmarried">Unmarried</label>
                    </div>
                    <div class="float-field">
                        <input type="number" name="sisters_nun" id="sisters_nun" value="{{ old('sisters_nun', $familyDetail?->sisters_nun ?? 0) }}" min="0" placeholder=" ">
                        <label for="sisters_nun">Nun</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-6 border-t border-gray-200">
            <a href="{{ route('register.complete') }}"
                class="w-full sm:w-auto text-center border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('onboarding.step2') }}" class="text-sm text-(--color-primary) hover:underline font-medium order-2 sm:order-1">Skip for now</a>
                <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                    class="w-full sm:w-auto bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors order-1 sm:order-2">
                    <span x-show="!submitting">Save & Continue</span>
                    <span x-show="submitting" x-cloak>Please wait...</span>
                </button>
            </div>
        </div>
    </form>
</x-layouts.onboarding>
