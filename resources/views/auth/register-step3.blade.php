<x-layouts.auth title="Step 3 - Registration" maxWidth="2xl">
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Step 3 of 5</span>
            <span class="text-sm text-gray-500">Family Details</span>
        </div>
        <div class="flex gap-1">
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
        </div>
    </div>

    <h2 class="text-xl font-serif font-bold text-gray-900 mb-6">Family Details</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store3') }}">
        @csrf

        {{-- ── Parents ──────────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Parents Information</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="father_name" class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                    <input type="text" name="father_name" id="father_name" value="{{ old('father_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('father_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="father_occupation" class="block text-sm font-medium text-gray-700 mb-1">Father's Occupation</label>
                    <input type="text" name="father_occupation" id="father_occupation" value="{{ old('father_occupation') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('father_occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mother_name" class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                    <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('mother_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mother_occupation" class="block text-sm font-medium text-gray-700 mb-1">Mother's Occupation</label>
                    <input type="text" name="mother_occupation" id="mother_occupation" value="{{ old('mother_occupation') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('mother_occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- House names & native places --}}
                <div>
                    <label for="father_house_name" class="block text-sm font-medium text-gray-700 mb-1">Father's House Name</label>
                    <input type="text" name="father_house_name" id="father_house_name" value="{{ old('father_house_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('father_house_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="father_native_place" class="block text-sm font-medium text-gray-700 mb-1">Father's Native Place</label>
                    <input type="text" name="father_native_place" id="father_native_place" value="{{ old('father_native_place') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('father_native_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mother_house_name" class="block text-sm font-medium text-gray-700 mb-1">Mother's House Name</label>
                    <input type="text" name="mother_house_name" id="mother_house_name" value="{{ old('mother_house_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('mother_house_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mother_native_place" class="block text-sm font-medium text-gray-700 mb-1">Mother's Native Place</label>
                    <input type="text" name="mother_native_place" id="mother_native_place" value="{{ old('mother_native_place') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('mother_native_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Siblings ─────────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Siblings</legend>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="num_brothers" class="block text-sm font-medium text-gray-700 mb-1">No. of Brothers</label>
                    <input type="number" name="num_brothers" id="num_brothers" value="{{ old('num_brothers', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('num_brothers') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brothers_married" class="block text-sm font-medium text-gray-700 mb-1">Brothers Married</label>
                    <input type="number" name="brothers_married" id="brothers_married" value="{{ old('brothers_married', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('brothers_married') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brothers_unmarried" class="block text-sm font-medium text-gray-700 mb-1">Brothers Unmarried</label>
                    <input type="number" name="brothers_unmarried" id="brothers_unmarried" value="{{ old('brothers_unmarried', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('brothers_unmarried') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brothers_priest" class="block text-sm font-medium text-gray-700 mb-1">Brothers (Priest / Religious)</label>
                    <input type="number" name="brothers_priest" id="brothers_priest" value="{{ old('brothers_priest', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('brothers_priest') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="num_sisters" class="block text-sm font-medium text-gray-700 mb-1">No. of Sisters</label>
                    <input type="number" name="num_sisters" id="num_sisters" value="{{ old('num_sisters', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('num_sisters') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sisters_married" class="block text-sm font-medium text-gray-700 mb-1">Sisters Married</label>
                    <input type="number" name="sisters_married" id="sisters_married" value="{{ old('sisters_married', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('sisters_married') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sisters_unmarried" class="block text-sm font-medium text-gray-700 mb-1">Sisters Unmarried</label>
                    <input type="number" name="sisters_unmarried" id="sisters_unmarried" value="{{ old('sisters_unmarried', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('sisters_unmarried') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sisters_nun" class="block text-sm font-medium text-gray-700 mb-1">Sisters (Nun / Religious)</label>
                    <input type="number" name="sisters_nun" id="sisters_nun" value="{{ old('sisters_nun', 0) }}" min="0" max="20"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('sisters_nun') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Family Info ──────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Family Background</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="family_type" class="block text-sm font-medium text-gray-700 mb-1">Family Type</label>
                    <select name="family_type" id="family_type"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Joint', 'Nuclear', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('family_type') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('family_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="family_values" class="block text-sm font-medium text-gray-700 mb-1">Family Values</label>
                    <select name="family_values" id="family_values"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Orthodox', 'Traditional', 'Moderate', 'Liberal'] as $opt)
                            <option value="{{ $opt }}" {{ old('family_values') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('family_values') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="family_status" class="block text-sm font-medium text-gray-700 mb-1">Family Status</label>
                    <select name="family_status" id="family_status"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Middle Class', 'Upper Middle Class', 'Rich', 'Affluent'] as $opt)
                            <option value="{{ $opt }}" {{ old('family_status') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('family_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="family_living_in" class="block text-sm font-medium text-gray-700 mb-1">Family Living In</label>
                    <input type="text" name="family_living_in" id="family_living_in" value="{{ old('family_living_in') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="City / Town">
                    @error('family_living_in') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="candidate_asset_details" class="block text-sm font-medium text-gray-700 mb-1">Candidate Asset Details</label>
                    <textarea name="candidate_asset_details" id="candidate_asset_details" rows="2"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Property, land, etc.">{{ old('candidate_asset_details') }}</textarea>
                    @error('candidate_asset_details') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="about_family" class="block text-sm font-medium text-gray-700 mb-1">About Family</label>
                    <textarea name="about_family" id="about_family" rows="3"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Briefly describe your family background">{{ old('about_family') }}</textarea>
                    @error('about_family') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Navigation ───────────────────────────────────── --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('register.step2') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                Continue &rarr;
            </button>
        </div>
    </form>
</x-layouts.auth>
