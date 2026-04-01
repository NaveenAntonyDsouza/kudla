@php $c = $profile->contactInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'contact') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <x-phone-input name="whatsapp_number" label="WhatsApp Number" :value="$c?->whatsapp_number ?? ''" />
        <x-phone-input name="secondary_phone" label="Secondary Phone" :value="$c?->secondary_phone ?? ''" />
        <x-phone-input name="residential_phone_number" label="Residential Phone" :value="$c?->residential_phone_number ?? ''" maxlength="20" />
        <div class="float-field">
            <select name="preferred_call_time"><option value="">Select</option>
                @foreach(['8 AM - 10 AM IST', '10 AM - 12 PM IST', '12 PM - 2 PM IST', '2 PM - 4 PM IST', '4 PM - 6 PM IST', '6 PM - 8 PM IST', '8 PM - 10 PM IST', 'Any Time'] as $opt)
                    <option value="{{ $opt }}" {{ ($c?->preferred_call_time ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Preferred Call Time</label>
        </div>
        <div class="float-field"><input type="email" name="alternate_email" value="{{ $c?->alternate_email ?? '' }}" maxlength="150" placeholder=" "><label>Alternate Email</label></div>
    </div>

    <p class="text-sm font-semibold text-gray-700 mt-6 mb-3">Communication Address</p>
    <div class="float-field">
        <textarea name="communication_address" rows="3" maxlength="200" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $c?->communication_address ?? '' }}</textarea>
        <label>Address</label>
    </div>

    <p class="text-sm font-semibold text-gray-700 mt-6 mb-3">Reference Person</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field"><input type="text" name="reference_name" value="{{ $c?->reference_name ?? '' }}" maxlength="100" placeholder=" "><label>Name</label></div>
        <div class="float-field"><input type="text" name="reference_relationship" value="{{ $c?->reference_relationship ?? '' }}" maxlength="50" placeholder=" "><label>Relationship</label></div>
        <x-phone-input name="reference_mobile" label="Mobile Number" :value="$c?->reference_mobile ?? ''" />
    </div>

    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
