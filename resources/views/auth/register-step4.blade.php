<x-layouts.registration title="Step 4 - Location & Contact" :step="4">

    <h2 class="text-lg font-semibold text-gray-900 mb-6">Location & Contact Information</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store4') }}" x-data="{
        nativeCountry: '{{ old('native_country', $locationInfo?->native_country ?? '') }}',
        nativeState: '{{ old('native_state', $locationInfo?->native_state ?? '') }}',
        nativeDistrict: '{{ old('native_district', $locationInfo?->native_district ?? '') }}',
        nativeStates: [],
        nativeDistricts: [],

        async fetchNativeStates() {
            if (!this.nativeCountry) {
                this.nativeStates = []; this.nativeDistricts = []; this.nativeState = ''; this.nativeDistrict = ''; return;
            }
            if (this.nativeCountry === 'India') {
                const response = await fetch('/api/cascade/states');
                this.nativeStates = await response.json();
            } else {
                const response = await fetch(`/api/cascade/countries?country=${encodeURIComponent(this.nativeCountry)}`);
                const data = await response.json();
                this.nativeStates = data.locations || [];
            }
            if (this.nativeState) this.fetchNativeDistricts();
        },

        async fetchNativeDistricts() {
            if (!this.nativeState || this.nativeCountry !== 'India') {
                this.nativeDistricts = []; this.nativeDistrict = ''; return;
            }
            const response = await fetch(`/api/cascade/districts?state=${encodeURIComponent(this.nativeState)}`);
            this.nativeDistricts = await response.json();
        },

        init() {
            if (this.nativeCountry) this.fetchNativeStates();
        }
    }">
        @csrf

        <div class="space-y-5">
            {{-- Native Country --}}
            <div class="float-field">
                <select name="native_country" id="native_country" x-model="nativeCountry" @change="fetchNativeStates(); nativeState=''; nativeDistrict=''; nativeDistricts=[];" required>
                    <option value="">Select</option>
                    @foreach(config('reference_data.country_list') as $group => $countries)
                        <optgroup label="{{ $group }}">
                            @foreach($countries as $c)
                                <option value="{{ $c }}" {{ old('native_country', $locationInfo?->native_country ?? '') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="native_country">Native Country <span class="text-red-500">*</span></label>
                @error('native_country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Native State --}}
            <div x-show="nativeCountry" x-transition class="float-field">
                <template x-if="nativeStates.length > 0">
                    <div>
                        <select name="native_state" id="native_state" x-model="nativeState" @change="fetchNativeDistricts(); nativeDistrict='';">
                            <option value="">Select</option>
                            <template x-for="st in nativeStates" :key="st">
                                <option :value="st" x-text="st" :selected="st === nativeState"></option>
                            </template>
                        </select>
                        <label for="native_state">Native State <span class="text-red-500">*</span></label>
                    </div>
                </template>
                <template x-if="nativeStates.length === 0">
                    <div>
                        <input type="text" name="native_state" id="native_state" x-model="nativeState" placeholder=" ">
                        <label for="native_state">Native State <span class="text-red-500">*</span></label>
                    </div>
                </template>
                @error('native_state') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Native District (India only) --}}
            <div x-show="nativeCountry === 'India' && nativeState" x-transition class="float-field">
                <template x-if="nativeDistricts.length > 0">
                    <div>
                        <select name="native_district" id="native_district" x-model="nativeDistrict">
                            <option value="">Select</option>
                            <template x-for="district in nativeDistricts" :key="district">
                                <option :value="district" x-text="district" :selected="district === nativeDistrict"></option>
                            </template>
                        </select>
                        <label for="native_district">Native District <span class="text-red-500">*</span></label>
                    </div>
                </template>
                <template x-if="nativeDistricts.length === 0">
                    <div>
                        <input type="text" name="native_district" id="native_district" placeholder=" ">
                        <label for="native_district">Native District <span class="text-red-500">*</span></label>
                    </div>
                </template>
                @error('native_district') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- WhatsApp Number --}}
            <x-phone-input name="whatsapp_number" label="WhatsApp Number" :value="$contactInfo?->whatsapp_number ?? ''" />
        </div>

        {{-- Primary Contact Details --}}
        <h2 class="text-lg font-semibold text-gray-900 mt-8 mb-6">Primary Contact Details</h2>

        <div class="space-y-5">
            {{-- Mobile Number (pre-filled, readonly) --}}
            <x-phone-input name="mobile_number" label="Mobile Number" :value="auth()->user()->phone ?? ''" :readonly="true" />
            <p class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                <svg class="w-3.5 h-3.5 text-(--color-primary)" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                </svg>
                We will send OTP to this mobile number for verification
            </p>

            {{-- Custodian Name --}}
            <div class="float-field">
                <input type="text" name="custodian_name" id="custodian_name" value="{{ old('custodian_name', $contactInfo?->contact_person ?? '') }}" placeholder=" ">
                <label for="custodian_name">Custodian Name</label>
                @error('custodian_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Custodian Relation --}}
            <div class="float-field">
                <input type="text" name="custodian_relation" id="custodian_relation" value="{{ old('custodian_relation', $contactInfo?->contact_relationship ?? '') }}" placeholder=" ">
                <label for="custodian_relation">Custodian Relation</label>
                @error('custodian_relation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Communication Address --}}
            <div class="float-field" x-data="{ count: {{ mb_strlen(old('communication_address', $contactInfo?->communication_address ?? '')) }} }">
                <textarea name="communication_address" id="communication_address" rows="3" required maxlength="200" placeholder=" "
                    @input="count = $el.value.length"
                    class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('communication_address', $contactInfo?->communication_address ?? '') }}</textarea>
                <label for="communication_address">Communication Address <span class="text-red-500">*</span></label>
                <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max 200 Chars.)</p>
                @error('communication_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- PIN/ZIP Code --}}
            <div class="float-field">
                <input type="text" name="pin_zip_code" id="pin_zip_code" value="{{ old('pin_zip_code', $locationInfo?->pin_zip_code ?? $contactInfo?->pincode ?? '') }}" maxlength="10" required placeholder=" ">
                <label for="pin_zip_code">PIN/ZIP Code <span class="text-red-500">*</span></label>
                @error('pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('register.step3') }}"
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
