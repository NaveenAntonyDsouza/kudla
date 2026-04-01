<x-layouts.onboarding title="Additional Info - Step 2" :step="2" :completionPct="$completionPct">

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

    <form method="POST" action="{{ route('onboarding.store2') }}" @submit="submitting = true" x-data="{
        submitting: false,
        residingCountry: '{{ old('residing_country', $defaultResidingCountry) }}',
        presentSameAsComm: {{ old('present_address_same_as_comm') ? 'true' : ($contactInfo?->present_address_same_as_comm ? 'true' : 'false') }},
        permSameAsComm: {{ old('permanent_address_same_as_comm') ? 'true' : ($contactInfo?->permanent_address_same_as_comm ? 'true' : 'false') }},
        permSameAsPresent: {{ old('permanent_address_same_as_present') ? 'true' : ($contactInfo?->permanent_address_same_as_present ? 'true' : 'false') }},
    }">
        @csrf

        {{-- ── Additional Location Information ───────────────────── --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Additional Location Information</h2>
        </div>

        <div class="space-y-5 mb-10">
            {{-- Residing Country --}}
            <div class="float-field">
                <select name="residing_country" id="residing_country" x-model="residingCountry">
                    <option value="">Select</option>
                    @foreach(config('reference_data.country_list') as $group => $countries)
                        <optgroup label="{{ $group }}">
                            @foreach($countries as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="residing_country">Residing Country <span class="text-red-500">*</span></label>
                @error('residing_country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Residency Status (conditional: show if residing country != India) --}}
            <div x-show="residingCountry && residingCountry !== 'India'" x-transition class="float-field">
                <select name="residency_status" id="residency_status">
                    <option value="">Select</option>
                    @foreach(['Permanent Resident', 'Citizen', 'Work Permit', 'Student Visa', 'Temporary Visa'] as $opt)
                        <option value="{{ $opt }}" {{ old('residency_status', $locationInfo?->residency_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="residency_status">Residential Status <span class="text-red-500">*</span></label>
                @error('residency_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Outstation Leave Dates (conditional: show if residing country != India) --}}
            <div x-show="residingCountry && residingCountry !== 'India'" x-transition>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Outstation Candidate's Next Leave Date</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="float-field">
                        <input type="date" name="outstation_leave_date_from" id="outstation_leave_date_from"
                            value="{{ old('outstation_leave_date_from', $locationInfo?->outstation_leave_date_from?->format('Y-m-d') ?? '') }}" placeholder=" ">
                        <label for="outstation_leave_date_from">From Date</label>
                        @error('outstation_leave_date_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="float-field">
                        <input type="date" name="outstation_leave_date_to" id="outstation_leave_date_to"
                            value="{{ old('outstation_leave_date_to', $locationInfo?->outstation_leave_date_to?->format('Y-m-d') ?? '') }}" placeholder=" ">
                        <label for="outstation_leave_date_to">To Date</label>
                        @error('outstation_leave_date_to') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Additional Contact Information ─────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Additional Contact Information</h2>

        <div class="space-y-5 mb-10">
            {{-- Residential Phone Number --}}
            <x-phone-input name="residential_phone_number" label="Residential Phone Number" :value="$contactInfo?->residential_phone_number ?? ''" maxlength="20" />

            {{-- Secondary Mobile Number --}}
            <x-phone-input name="secondary_phone" label="Secondary Mobile Number" :value="$contactInfo?->secondary_phone ?? ''" />

            {{-- Primary Mobile (read-only) --}}
            <x-phone-input name="primary_mobile_display" label="Primary Mobile Number" :value="auth()->user()->phone" :readonly="true" />

            {{-- Preferred Call Time --}}
            <div class="float-field">
                <select name="preferred_call_time" id="preferred_call_time">
                    <option value="">Select</option>
                    @foreach([
                        '8 AM - 10 AM IST',
                        '10 AM - 12 PM IST',
                        '12 PM - 2 PM IST',
                        '2 PM - 4 PM IST',
                        '4 PM - 6 PM IST',
                        '6 PM - 8 PM IST',
                        '8 PM - 10 PM IST',
                        'Any Time',
                    ] as $opt)
                        <option value="{{ $opt }}" {{ old('preferred_call_time', $contactInfo?->preferred_call_time ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="preferred_call_time">Preferred time we can call to reach you?</label>
                @error('preferred_call_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Candidate's Present Address ────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Candidate's Present Address</h2>

        <div class="space-y-5 mb-10">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="present_address_same_as_comm" value="1" x-model="presentSameAsComm"
                    class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                Same as Communication Address
            </label>

            <div x-show="!presentSameAsComm" x-transition class="space-y-5">
                <div class="float-field" x-data="{ count: {{ mb_strlen(old('present_address', $contactInfo?->present_address ?? '')) }} }">
                    <textarea name="present_address" id="present_address" rows="3" maxlength="200" placeholder=" "
                        @input="count = $el.value.length"
                        class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('present_address', $contactInfo?->present_address ?? '') }}</textarea>
                    <label for="present_address">Present Address <span class="text-red-500">*</span></label>
                    <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 200 Chars.)</p>
                    @error('present_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="float-field">
                    <input type="text" name="present_pin_zip_code" id="present_pin_zip_code"
                        value="{{ old('present_pin_zip_code', $contactInfo?->present_pin_zip_code ?? '') }}" maxlength="10" placeholder=" ">
                    <label for="present_pin_zip_code">PIN/ZIP Code <span class="text-red-500">*</span></label>
                    @error('present_pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ── Candidate's Permanent Address ──────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Candidate's Permanent Address</h2>

        <div class="space-y-5 mb-10">
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="permanent_address_same_as_comm" value="1" x-model="permSameAsComm"
                        @change="if (permSameAsComm) permSameAsPresent = false"
                        class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                    Same as Communication Address
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="permanent_address_same_as_present" value="1" x-model="permSameAsPresent"
                        @change="if (permSameAsPresent) permSameAsComm = false"
                        class="rounded border-gray-300 text-(--color-primary) focus:ring-(--color-primary)">
                    Same as Present Address
                </label>
            </div>

            <div x-show="!permSameAsComm && !permSameAsPresent" x-transition class="space-y-5">
                <div class="float-field" x-data="{ count: {{ mb_strlen(old('permanent_address', $contactInfo?->permanent_address ?? '')) }} }">
                    <textarea name="permanent_address" id="permanent_address" rows="3" maxlength="200" placeholder=" "
                        @input="count = $el.value.length"
                        class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ old('permanent_address', $contactInfo?->permanent_address ?? '') }}</textarea>
                    <label for="permanent_address">Permanent Address</label>
                    <p class="mt-1 text-xs text-gray-400"><span x-text="count">0</span> Characters Typed (Max. 200 Chars.)</p>
                    @error('permanent_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="float-field">
                    <input type="text" name="permanent_pin_zip_code" id="permanent_pin_zip_code"
                        value="{{ old('permanent_pin_zip_code', $contactInfo?->permanent_pin_zip_code ?? '') }}" maxlength="10" placeholder=" ">
                    <label for="permanent_pin_zip_code">PIN/ZIP Code</label>
                    @error('permanent_pin_zip_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- ── Additional Fields ──────────────────────────────── --}}
        <div class="space-y-5 mb-10">
            <div class="float-field">
                <input type="email" name="alternate_email" id="alternate_email"
                    value="{{ old('alternate_email', $contactInfo?->alternate_email ?? '') }}" maxlength="150" placeholder=" ">
                <label for="alternate_email">Alternate Email ID</label>
                @error('alternate_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="reference_name" id="reference_name"
                    value="{{ old('reference_name', $contactInfo?->reference_name ?? '') }}" maxlength="100" placeholder=" ">
                <label for="reference_name">Reference Person's Name</label>
                @error('reference_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="text" name="reference_relationship" id="reference_relationship"
                    value="{{ old('reference_relationship', $contactInfo?->reference_relationship ?? '') }}" maxlength="50" placeholder=" ">
                <label for="reference_relationship">Reference Person's Relationship with Candidate</label>
                @error('reference_relationship') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <x-phone-input name="reference_mobile" label="Reference Person's Mobile Number" :value="$contactInfo?->reference_mobile ?? ''" />
        </div>

        {{-- Navigation --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-6 border-t border-gray-200">
            <a href="{{ route('onboarding.step1') }}"
                class="w-full sm:w-auto text-center border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('onboarding.preferences') }}" class="text-sm text-(--color-primary) hover:underline font-medium order-2 sm:order-1">Skip for now</a>
                <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                    class="w-full sm:w-auto bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors order-1 sm:order-2">
                    <span x-show="!submitting">Save</span>
                    <span x-show="submitting" x-cloak>Please wait...</span>
                </button>
            </div>
        </div>
    </form>
</x-layouts.onboarding>
