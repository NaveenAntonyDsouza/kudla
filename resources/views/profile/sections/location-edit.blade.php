@php $l = $profile->locationInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'location') }}" x-data="{ submitting: false, residingCountry: '{{ $l?->residing_country ?? '' }}' }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field"><input type="text" name="native_country" value="{{ $l?->native_country ?? '' }}" maxlength="100" placeholder=" "><label>Native Country</label></div>
        <div class="float-field"><input type="text" name="native_state" value="{{ $l?->native_state ?? '' }}" maxlength="100" placeholder=" "><label>Native State</label></div>
        <div class="float-field"><input type="text" name="native_district" value="{{ $l?->native_district ?? '' }}" maxlength="100" placeholder=" "><label>Native District</label></div>
        <div class="float-field"><input type="text" name="pin_zip_code" value="{{ $l?->pin_zip_code ?? '' }}" maxlength="10" placeholder=" "><label>PIN / ZIP Code</label></div>
        <div class="float-field">
            <select name="residing_country" x-model="residingCountry">
                <option value="">Select</option>
                @foreach(config('reference_data.country_list') as $group => $countries)
                    <optgroup label="{{ $group }}">
                        @foreach($countries as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select><label>Residing Country</label>
        </div>
        <div x-show="residingCountry && residingCountry !== 'India'" class="float-field">
            <select name="residency_status"><option value="">Select</option>
                @foreach(['Permanent Resident', 'Citizen', 'Work Permit', 'Student Visa', 'Temporary Visa'] as $opt)
                    <option value="{{ $opt }}" {{ ($l?->residency_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Residency Status</label>
        </div>
        <div x-show="residingCountry && residingCountry !== 'India'" class="float-field">
            <input type="date" name="outstation_leave_date_from" value="{{ $l?->outstation_leave_date_from?->format('Y-m-d') ?? '' }}" placeholder=" "><label>Leave From</label>
        </div>
        <div x-show="residingCountry && residingCountry !== 'India'" class="float-field">
            <input type="date" name="outstation_leave_date_to" value="{{ $l?->outstation_leave_date_to?->format('Y-m-d') ?? '' }}" placeholder=" "><label>Leave To</label>
        </div>
    </div>
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
