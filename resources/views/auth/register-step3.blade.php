<x-layouts.registration title="Step 3 - Education & Professional" :step="3">

    <h2 class="text-lg font-semibold text-gray-900 mb-6">Education & Professional Information</h2>

    @if ($errors?->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below:</p>
            <ul class="mt-1 text-xs text-red-500 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store3') }}" x-data="{
        workingCountry: '{{ old('working_country', $educationDetail?->working_country ?? '') }}',
        workingState: '{{ old('working_state', $educationDetail?->working_state ?? '') }}',
        workingDistrict: '{{ old('working_district', $educationDetail?->working_district ?? '') }}',
        states: [],
        districts: [],

        async fetchStates() {
            if (!this.workingCountry) {
                this.states = []; this.districts = []; this.workingState = ''; this.workingDistrict = ''; return;
            }
            if (this.workingCountry === 'India') {
                const response = await fetch('/api/cascade/states');
                this.states = await response.json();
            } else {
                const response = await fetch(`/api/cascade/countries?country=${encodeURIComponent(this.workingCountry)}`);
                const data = await response.json();
                this.states = data.locations || [];
            }
            if (this.workingState) this.fetchDistricts();
        },

        async fetchDistricts() {
            if (!this.workingState || this.workingCountry !== 'India') {
                this.districts = []; this.workingDistrict = ''; return;
            }
            const response = await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.workingState)}`);
            this.districts = await response.json();
        },

        init() { if (this.workingCountry) this.fetchStates(); }
    }">
        @csrf

        <div class="space-y-5">
            {{-- Educational Qualifications (grouped) --}}
            <div class="float-field">
                <select name="highest_education" id="highest_education" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.educational_qualifications_list') as $category => $options)
                        <optgroup label="{{ $category }}">
                            @foreach($options as $opt)
                                <option value="{{ $opt }}" {{ old('highest_education', $educationDetail?->highest_education ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="highest_education">Educational Qualifications <span class="text-red-500">*</span></label>
                @error('highest_education') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Education Level --}}
            <div class="float-field">
                <select name="education_level" id="education_level" required>
                    <option value="">Select</option>
                    @foreach(['High School', 'Diploma', "Bachelor's", "Master's", 'PhD', 'PG Diploma'] as $opt)
                        <option value="{{ $opt }}" {{ old('education_level', $educationDetail?->education_level ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="education_level">Education Level <span class="text-red-500">*</span></label>
                @error('education_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Education Detail --}}
            <div class="float-field">
                <input type="text" name="education_detail" id="education_detail" value="{{ old('education_detail', $educationDetail?->education_detail ?? '') }}" placeholder=" ">
                <label for="education_detail">Education Detail / Specialization</label>
                @error('education_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- College --}}
            <div class="float-field">
                <input type="text" name="college_name" id="college_name" value="{{ old('college_name', $educationDetail?->college_name ?? '') }}" placeholder=" ">
                <label for="college_name">College / University</label>
                @error('college_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Occupation Category (grouped) --}}
            <div class="float-field">
                <select name="occupation" id="occupation" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.occupation_category_list') as $category => $options)
                        <optgroup label="{{ $category }}">
                            @foreach($options as $opt)
                                <option value="{{ $opt }}" {{ old('occupation', $educationDetail?->occupation ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="occupation">Occupation Category <span class="text-red-500">*</span></label>
                @error('occupation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Occupation Detail --}}
            <div class="float-field">
                <input type="text" name="occupation_detail" id="occupation_detail" value="{{ old('occupation_detail', $educationDetail?->occupation_detail ?? '') }}" placeholder=" ">
                <label for="occupation_detail">Occupation Detail</label>
                @error('occupation_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Employment Category --}}
            <div class="float-field">
                <select name="employment_category" id="employment_category" required>
                    <option value="">Select</option>
                    @foreach(['Central Govt.', 'Entrepreneurship', 'Govt.', 'MNC', 'Others', 'Overseas', 'Own Business', 'Private', 'Public Limited', 'Semi Govt.'] as $opt)
                        <option value="{{ $opt }}" {{ old('employment_category', $educationDetail?->employment_category ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="employment_category">Employment Category <span class="text-red-500">*</span></label>
                @error('employment_category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Employer --}}
            <div class="float-field">
                <input type="text" name="employer_name" id="employer_name" value="{{ old('employer_name', $educationDetail?->employer_name ?? '') }}" placeholder=" ">
                <label for="employer_name">Employer / Company Name</label>
                @error('employer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Working Country (grouped) --}}
            <div class="float-field">
                <select name="working_country" id="working_country" x-model="workingCountry" @change="fetchStates(); districts=[]; workingState=''; workingDistrict='';" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.country_list') as $group => $countries)
                        <optgroup label="{{ $group }}">
                            @foreach($countries as $country)
                                <option value="{{ $country }}" {{ old('working_country', $educationDetail?->working_country ?? '') === $country ? 'selected' : '' }}>{{ $country }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="working_country">Working Country <span class="text-red-500">*</span></label>
                @error('working_country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Working State --}}
            <div x-show="workingCountry" x-transition class="float-field">
                <template x-if="states.length > 0">
                    <div>
                        <select name="working_state" id="working_state" x-model="workingState" @change="fetchDistricts(); workingDistrict='';">
                            <option value="">Select</option>
                            <template x-for="state in states" :key="state">
                                <option :value="state" x-text="state" :selected="state === workingState"></option>
                            </template>
                        </select>
                        <label for="working_state">Working State</label>
                    </div>
                </template>
                <template x-if="states.length === 0">
                    <div>
                        <input type="text" name="working_state" x-model="workingState" value="{{ old('working_state') }}" placeholder=" ">
                        <label for="working_state">Working State</label>
                    </div>
                </template>
                @error('working_state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Working District (India only) --}}
            <div x-show="workingCountry === 'India' && workingState && districts.length > 0" x-transition class="float-field">
                <select name="working_district" id="working_district" x-model="workingDistrict">
                    <option value="">Select</option>
                    <template x-for="district in districts" :key="district">
                        <option :value="district" x-text="district" :selected="district === workingDistrict"></option>
                    </template>
                </select>
                <label for="working_district">Working District</label>
                @error('working_district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Annual Income --}}
            <div class="float-field">
                <select name="annual_income" id="annual_income" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.annual_income_list') as $opt)
                        <option value="{{ $opt }}" {{ old('annual_income', $educationDetail?->annual_income ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="annual_income">Annual Income <span class="text-red-500">*</span></label>
                @error('annual_income') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('register.step2') }}"
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
