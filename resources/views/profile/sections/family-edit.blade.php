@php $f = $profile->familyDetail; @endphp
<form method="POST" action="{{ route('profile.update', 'family') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            <select name="family_status"><option value="">Select</option>
                @foreach(['Middle Class', 'Upper Middle Class', 'Rich', 'Affluent'] as $opt)
                    <option value="{{ $opt }}" {{ ($f?->family_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Family Status</label>
        </div>
        <div class="float-field"><input type="text" name="father_name" value="{{ $f?->father_name ?? '' }}" maxlength="100" placeholder=" "><label>Father's Name</label></div>
        <div class="float-field"><input type="text" name="father_house_name" value="{{ $f?->father_house_name ?? '' }}" maxlength="100" placeholder=" "><label>Father's Family Name / Surname</label></div>
        <div class="float-field"><input type="text" name="father_native_place" value="{{ $f?->father_native_place ?? '' }}" maxlength="100" placeholder=" "><label>Father's Native Place</label></div>
        <div class="float-field"><input type="text" name="father_occupation" value="{{ $f?->father_occupation ?? '' }}" maxlength="100" placeholder=" "><label>Father's Occupation</label></div>
        <div class="float-field"><input type="text" name="mother_name" value="{{ $f?->mother_name ?? '' }}" maxlength="100" placeholder=" "><label>Mother's Name</label></div>
        <div class="float-field"><input type="text" name="mother_house_name" value="{{ $f?->mother_house_name ?? '' }}" maxlength="100" placeholder=" "><label>Mother's Maiden Family Name</label></div>
        <div class="float-field"><input type="text" name="mother_native_place" value="{{ $f?->mother_native_place ?? '' }}" maxlength="100" placeholder=" "><label>Mother's Native Place</label></div>
        <div class="float-field"><input type="text" name="mother_occupation" value="{{ $f?->mother_occupation ?? '' }}" maxlength="100" placeholder=" "><label>Mother's Occupation</label></div>
    </div>

    <p class="text-sm font-semibold text-gray-700 mt-6 mb-3">Sibling Details</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
        <div class="float-field"><input type="number" name="brothers_married" value="{{ $f?->brothers_married ?? 0 }}" min="0" placeholder=" "><label>Brothers Married</label></div>
        <div class="float-field"><input type="number" name="brothers_unmarried" value="{{ $f?->brothers_unmarried ?? 0 }}" min="0" placeholder=" "><label>Brothers Unmarried</label></div>
        <div class="float-field"><input type="number" name="brothers_priest" value="{{ $f?->brothers_priest ?? 0 }}" min="0" placeholder=" "><label>Brothers Priest</label></div>
        <div class="float-field"><input type="number" name="sisters_married" value="{{ $f?->sisters_married ?? 0 }}" min="0" placeholder=" "><label>Sisters Married</label></div>
        <div class="float-field"><input type="number" name="sisters_unmarried" value="{{ $f?->sisters_unmarried ?? 0 }}" min="0" placeholder=" "><label>Sisters Unmarried</label></div>
        <div class="float-field"><input type="number" name="sisters_nun" value="{{ $f?->sisters_nun ?? 0 }}" min="0" placeholder=" "><label>Sisters Nun</label></div>
    </div>

    <div class="mt-5 float-field">
        <textarea name="candidate_asset_details" rows="3" maxlength="500" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $f?->candidate_asset_details ?? '' }}</textarea>
        <label>Candidate's Asset Details</label>
    </div>
    <div class="mt-5 float-field">
        <textarea name="about_candidate_family" rows="4" maxlength="5000" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $f?->about_candidate_family ?? '' }}</textarea>
        <label>About Candidate's Family</label>
    </div>

    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
