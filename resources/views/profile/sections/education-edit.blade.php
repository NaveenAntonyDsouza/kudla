@php $e = $profile->educationDetail; @endphp
<form method="POST" action="{{ route('profile.update', 'education') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            <select name="highest_education"><option value="">Select</option>
                @foreach(config('reference_data.educational_qualifications_list', []) as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $opt)
                            <option value="{{ $opt }}" {{ ($e?->highest_education ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select><label>Highest Education</label>
        </div>
        <div class="float-field"><input type="text" name="education_detail" value="{{ $e?->education_detail ?? '' }}" maxlength="200" placeholder=" "><label>Education Detail</label></div>
        <div class="float-field"><input type="text" name="college_name" value="{{ $e?->college_name ?? '' }}" maxlength="100" placeholder=" "><label>College / University</label></div>
        <div class="float-field">
            <select name="occupation"><option value="">Select</option>
                @foreach(config('reference_data.occupation_category_list', []) as $group => $items)
                    <optgroup label="{{ $group }}">
                        @foreach($items as $opt)
                            <option value="{{ $opt }}" {{ ($e?->occupation ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select><label>Occupation</label>
        </div>
        <div class="float-field"><input type="text" name="occupation_detail" value="{{ $e?->occupation_detail ?? '' }}" maxlength="200" placeholder=" "><label>Occupation Detail</label></div>
        <div class="float-field"><input type="text" name="employer_name" value="{{ $e?->employer_name ?? '' }}" maxlength="100" placeholder=" "><label>Employer Name</label></div>
        <div class="float-field">
            <select name="annual_income"><option value="">Select</option>
                @foreach(config('reference_data.annual_income_list', []) as $opt)
                    <option value="{{ $opt }}" {{ ($e?->annual_income ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Annual Income</label>
        </div>
        <div class="float-field"><input type="text" name="working_country" value="{{ $e?->working_country ?? '' }}" maxlength="100" placeholder=" "><label>Working Country</label></div>
        <div class="float-field"><input type="text" name="working_state" value="{{ $e?->working_state ?? '' }}" maxlength="100" placeholder=" "><label>Working State</label></div>
        <div class="float-field"><input type="text" name="working_district" value="{{ $e?->working_district ?? '' }}" maxlength="100" placeholder=" "><label>Working District</label></div>
    </div>
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
