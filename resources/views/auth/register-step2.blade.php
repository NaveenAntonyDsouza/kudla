<x-layouts.auth title="Step 2 - Registration" maxWidth="2xl">
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Step 2 of 5</span>
            <span class="text-sm text-gray-500">Education & Career</span>
        </div>
        <div class="flex gap-1">
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
        </div>
    </div>

    <h2 class="text-xl font-serif font-bold text-gray-900 mb-6">Education & Professional Details</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store2') }}" x-data="{
        workingCountry: '{{ old('working_country', '') }}',
        workingState: '{{ old('working_state', '') }}',
        workingDistrict: '{{ old('working_district', '') }}',
        states: [],
        districts: [],

        async fetchStates() {
            if (this.workingCountry !== 'India') {
                this.states = [];
                this.districts = [];
                this.workingState = '';
                this.workingDistrict = '';
                return;
            }
            const response = await fetch('/api/cascade/states');
            this.states = await response.json();
            if (this.workingState) {
                this.fetchDistricts();
            }
        },

        async fetchDistricts() {
            if (!this.workingState) {
                this.districts = [];
                this.workingDistrict = '';
                return;
            }
            const response = await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.workingState)}`);
            this.districts = await response.json();
        },

        init() {
            if (this.workingCountry) this.fetchStates();
        }
    }">
        @csrf

        {{-- ── Education ────────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Education</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="highest_education" class="block text-sm font-medium text-gray-700 mb-1">Highest Education <span class="text-red-500">*</span></label>
                    <select name="highest_education" id="highest_education" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Below 10th', '10th / SSLC', '12th / PUC', 'Diploma', 'Bachelor\'s Degree', 'Master\'s Degree', 'Doctorate / PhD', 'Professional Degree', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('highest_education') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('highest_education') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="education_level" class="block text-sm font-medium text-gray-700 mb-1">Education Level</label>
                    <select name="education_level" id="education_level"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Under Graduate', 'Graduate', 'Post Graduate', 'Doctorate'] as $opt)
                            <option value="{{ $opt }}" {{ old('education_level') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('education_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="education_detail" class="block text-sm font-medium text-gray-700 mb-1">Education Detail / Specialization</label>
                    <input type="text" name="education_detail" id="education_detail" value="{{ old('education_detail') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="e.g. B.E. in Computer Science">
                    @error('education_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="college_name" class="block text-sm font-medium text-gray-700 mb-1">College / University</label>
                    <input type="text" name="college_name" id="college_name" value="{{ old('college_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Name of institution">
                    @error('college_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Professional ─────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Professional Details</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="occupation" class="block text-sm font-medium text-gray-700 mb-1">Occupation <span class="text-red-500">*</span></label>
                    <select name="occupation" id="occupation" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Software Professional', 'Engineer', 'Doctor', 'Teacher / Professor', 'Government Employee', 'Business / Self Employed', 'Accountant', 'Banking Professional', 'Lawyer', 'Nurse', 'Scientist', 'Defence', 'Civil Services', 'Student', 'Not Working', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('occupation') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="occupation_detail" class="block text-sm font-medium text-gray-700 mb-1">Occupation Detail</label>
                    <input type="text" name="occupation_detail" id="occupation_detail" value="{{ old('occupation_detail') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Specific role / designation">
                    @error('occupation_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="employment_category" class="block text-sm font-medium text-gray-700 mb-1">Employment Category</label>
                    <select name="employment_category" id="employment_category"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Private', 'Government / PSU', 'Business / Self Employed', 'Defence', 'Not Working'] as $opt)
                            <option value="{{ $opt }}" {{ old('employment_category') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('employment_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="employer_name" class="block text-sm font-medium text-gray-700 mb-1">Employer / Company Name</label>
                    <input type="text" name="employer_name" id="employer_name" value="{{ old('employer_name') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Company name">
                    @error('employer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="annual_income" class="block text-sm font-medium text-gray-700 mb-1">Annual Income</label>
                    <select name="annual_income" id="annual_income"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Below 1 Lakh', '1-2 Lakh', '2-4 Lakh', '4-6 Lakh', '6-8 Lakh', '8-10 Lakh', '10-15 Lakh', '15-20 Lakh', '20-30 Lakh', '30-50 Lakh', '50 Lakh - 1 Crore', 'Above 1 Crore', 'Not Specified'] as $opt)
                            <option value="{{ $opt }}" {{ old('annual_income') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('annual_income') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Working Location ─────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Working Location</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="working_country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <select name="working_country" id="working_country" x-model="workingCountry" @change="fetchStates(); districts=[]; workingState=''; workingDistrict='';"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(config('locations.countries') as $country)
                            <option value="{{ $country }}">{{ $country }}</option>
                        @endforeach
                    </select>
                    @error('working_country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="workingCountry === 'India'" x-transition>
                    <label for="working_state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select name="working_state" id="working_state" x-model="workingState" @change="fetchDistricts(); workingDistrict='';"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select State</option>
                        <template x-for="state in states" :key="state">
                            <option :value="state" x-text="state" :selected="state === workingState"></option>
                        </template>
                    </select>
                    @error('working_state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="workingCountry === 'India' && workingState" x-transition>
                    <label for="working_district" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <template x-if="districts.length > 0">
                        <div>
                            <select name="working_district" id="working_district" x-model="workingDistrict"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select District</option>
                                <template x-for="district in districts" :key="district">
                                    <option :value="district" x-text="district" :selected="district === workingDistrict"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                    <template x-if="districts.length === 0">
                        <div>
                            <input type="text" name="working_district" value="{{ old('working_district') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                                placeholder="District name">
                        </div>
                    </template>
                    @error('working_district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="working_city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="working_city" id="working_city" value="{{ old('working_city') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="City / Town">
                    @error('working_city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Navigation ───────────────────────────────────── --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                Continue &rarr;
            </button>
        </div>
    </form>
</x-layouts.auth>
