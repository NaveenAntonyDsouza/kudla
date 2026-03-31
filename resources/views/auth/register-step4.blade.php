<x-layouts.auth title="Step 4 - Registration" maxWidth="2xl">
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Step 4 of 5</span>
            <span class="text-sm text-gray-500">Location & Contact</span>
        </div>
        <div class="flex gap-1">
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-gray-200"></div>
        </div>
    </div>

    <h2 class="text-xl font-serif font-bold text-gray-900 mb-6">Location & Contact Details</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store4') }}" x-data="{
        country: '{{ old('country', '') }}',
        currentState: '{{ old('state', '') }}',
        nativeCountry: '{{ old('native_country', '') }}',
        nativeState: '{{ old('native_state', '') }}',
        nativeDistrict: '{{ old('native_district', '') }}',
        currentStates: [],
        nativeStates: [],
        nativeDistricts: [],

        async fetchCurrentStates() {
            if (this.country !== 'India') {
                this.currentStates = [];
                this.currentState = '';
                return;
            }
            const response = await fetch('/api/cascade/states');
            this.currentStates = await response.json();
        },

        async fetchNativeStates() {
            if (this.nativeCountry !== 'India') {
                this.nativeStates = [];
                this.nativeDistricts = [];
                this.nativeState = '';
                this.nativeDistrict = '';
                return;
            }
            const response = await fetch('/api/cascade/states');
            this.nativeStates = await response.json();
            if (this.nativeState) {
                this.fetchNativeDistricts();
            }
        },

        async fetchNativeDistricts() {
            if (!this.nativeState) {
                this.nativeDistricts = [];
                this.nativeDistrict = '';
                return;
            }
            const response = await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.nativeState)}`);
            this.nativeDistricts = await response.json();
        },

        init() {
            if (this.country) this.fetchCurrentStates();
            if (this.nativeCountry) this.fetchNativeStates();
        }
    }">
        @csrf

        {{-- ── Current Location ─────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Current Location</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <select name="country" id="country" x-model="country" @change="fetchCurrentStates(); currentState='';" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(config('locations.countries') as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="country === 'India'" x-transition>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select name="state" id="state" x-model="currentState"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select State</option>
                        <template x-for="st in currentStates" :key="st">
                            <option :value="st" x-text="st" :selected="st === currentState"></option>
                        </template>
                    </select>
                    @error('state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="City / Town">
                    @error('city') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="pin_zip_code" class="block text-sm font-medium text-gray-700 mb-1">PIN / ZIP Code</label>
                    <input type="text" name="pin_zip_code" id="pin_zip_code" value="{{ old('pin_zip_code') }}" maxlength="10"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="e.g. 575001">
                    @error('pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="citizenship" class="block text-sm font-medium text-gray-700 mb-1">Citizenship</label>
                    <input type="text" name="citizenship" id="citizenship" value="{{ old('citizenship', 'Indian') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    @error('citizenship') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="residency_status" class="block text-sm font-medium text-gray-700 mb-1">Residency Status</label>
                    <select name="residency_status" id="residency_status"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Citizen', 'Permanent Resident', 'Work Permit', 'Student Visa', 'Temporary Visa', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('residency_status') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('residency_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="grew_up_in" class="block text-sm font-medium text-gray-700 mb-1">Grew Up In</label>
                    <input type="text" name="grew_up_in" id="grew_up_in" value="{{ old('grew_up_in') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="City / Country">
                    @error('grew_up_in') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Native Place ─────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Native Place</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="native_country" class="block text-sm font-medium text-gray-700 mb-1">Native Country</label>
                    <select name="native_country" id="native_country" x-model="nativeCountry" @change="fetchNativeStates(); nativeState=''; nativeDistrict='';"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        <option value="India">India</option>
                        <option value="Other">Other</option>
                    </select>
                    @error('native_country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="nativeCountry === 'India'" x-transition>
                    <label for="native_state" class="block text-sm font-medium text-gray-700 mb-1">Native State</label>
                    <select name="native_state" id="native_state" x-model="nativeState" @change="fetchNativeDistricts(); nativeDistrict='';"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select State</option>
                        <template x-for="st in nativeStates" :key="st">
                            <option :value="st" x-text="st" :selected="st === nativeState"></option>
                        </template>
                    </select>
                    @error('native_state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-show="nativeCountry === 'India' && nativeState" x-transition>
                    <label for="native_district" class="block text-sm font-medium text-gray-700 mb-1">Native District</label>
                    <template x-if="nativeDistricts.length > 0">
                        <div>
                            <select name="native_district" id="native_district" x-model="nativeDistrict"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                                <option value="">Select District</option>
                                <template x-for="district in nativeDistricts" :key="district">
                                    <option :value="district" x-text="district" :selected="district === nativeDistrict"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                    <template x-if="nativeDistricts.length === 0">
                        <div>
                            <input type="text" name="native_district" value="{{ old('native_district') }}"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                                placeholder="District name">
                        </div>
                    </template>
                    @error('native_district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="native_place" class="block text-sm font-medium text-gray-700 mb-1">Native Place / Village</label>
                    <input type="text" name="native_place" id="native_place" value="{{ old('native_place') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Village / Town">
                    @error('native_place') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Contact Information ──────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Contact Information</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person') }}"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Name of contact person">
                    @error('contact_person') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="contact_relationship" class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                    <select name="contact_relationship" id="contact_relationship"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                        <option value="">Select</option>
                        @foreach(['Self', 'Father', 'Mother', 'Brother', 'Sister', 'Uncle', 'Aunt', 'Guardian', 'Other'] as $opt)
                            <option value="{{ $opt }}" {{ old('contact_relationship') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('contact_relationship') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="primary_phone" class="block text-sm font-medium text-gray-700 mb-1">Primary Phone</label>
                    <input type="tel" name="primary_phone" id="primary_phone" value="{{ old('primary_phone') }}" maxlength="15"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Phone number">
                    @error('primary_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="secondary_phone" class="block text-sm font-medium text-gray-700 mb-1">Secondary Phone</label>
                    <input type="tel" name="secondary_phone" id="secondary_phone" value="{{ old('secondary_phone') }}" maxlength="15"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Alternate number">
                    @error('secondary_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                    <input type="tel" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number') }}" maxlength="15"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="WhatsApp number">
                    @error('whatsapp_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- ── Address ──────────────────────────────────────── --}}
        <fieldset class="mb-6">
            <legend class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Address</legend>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="communication_address" class="block text-sm font-medium text-gray-700 mb-1">Communication Address</label>
                    <textarea name="communication_address" id="communication_address" rows="2"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                        placeholder="Full communication address">{{ old('communication_address') }}</textarea>
                    @error('communication_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="present_address" class="block text-sm font-medium text-gray-700 mb-1">Present Address</label>
                        <textarea name="present_address" id="present_address" rows="2"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            placeholder="Present residential address">{{ old('present_address') }}</textarea>
                        @error('present_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="present_pin_zip_code" class="block text-sm font-medium text-gray-700 mb-1">Present PIN / ZIP</label>
                        <input type="text" name="present_pin_zip_code" id="present_pin_zip_code" value="{{ old('present_pin_zip_code') }}" maxlength="10"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            placeholder="e.g. 575001">
                        @error('present_pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="permanent_address" class="block text-sm font-medium text-gray-700 mb-1">Permanent Address</label>
                        <textarea name="permanent_address" id="permanent_address" rows="2"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            placeholder="Permanent address">{{ old('permanent_address') }}</textarea>
                        @error('permanent_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="permanent_pin_zip_code" class="block text-sm font-medium text-gray-700 mb-1">Permanent PIN / ZIP</label>
                        <input type="text" name="permanent_pin_zip_code" id="permanent_pin_zip_code" value="{{ old('permanent_pin_zip_code') }}" maxlength="10"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                            placeholder="e.g. 575001">
                        @error('permanent_pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- ── Navigation ───────────────────────────────────── --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('register.step3') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                Continue &rarr;
            </button>
        </div>
    </form>
</x-layouts.auth>
