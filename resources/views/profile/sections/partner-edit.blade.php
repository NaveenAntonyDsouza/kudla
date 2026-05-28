@php $pp = $profile->partnerPreference; @endphp
<form method="POST" action="{{ route('profile.update', 'partner') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            <select name="age_from"><option value="">Any</option>
                @for($i = 18; $i <= 70; $i++)
                    <option value="{{ $i }}" {{ ($pp?->age_from ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select><label>Age From</label>
        </div>
        <div class="float-field">
            <select name="age_to"><option value="">Any</option>
                @for($i = 18; $i <= 70; $i++)
                    <option value="{{ $i }}" {{ ($pp?->age_to ?? '') == $i ? 'selected' : '' }}>{{ $i }}</option>
                @endfor
            </select><label>Age To</label>
        </div>
        <div class="float-field">
            <select name="height_from"><option value="">Any</option>
                @foreach(config('reference_data.height_list', []) as $h)
                    <option value="{{ $h }}" {{ ($pp?->height_from_cm ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
            </select><label>Height From</label>
        </div>
        <div class="float-field">
            <select name="height_to"><option value="">Any</option>
                @foreach(config('reference_data.height_list', []) as $h)
                    <option value="{{ $h }}" {{ ($pp?->height_to_cm ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
            </select><label>Height To</label>
        </div>
    </div>

    @php
        // For partner-preference multi-selects: merge active options with
        // any values the user has already selected. Otherwise an admin's
        // deactivation of (say) "Jain" would silently hide it from this
        // user's selected religions even though the value is still
        // stored — they'd see no checkbox + save would drop it. The
        // merge keeps deactivated-but-selected values visible & checked.
        $mergeWithSelected = fn (array $active, ?array $selected) =>
            array_values(array_unique(array_merge($active, $selected ?? [])));
    @endphp
    <div class="space-y-4 mt-5">
        <x-multi-select name="marital_status" label="Marital Status" :options="$mergeWithSelected(config('reference_data.marital_status_list', []), $pp?->marital_status)" :selected="$pp?->marital_status ?? []" />
        <x-multi-select name="complexion" label="Complexion" :options="$mergeWithSelected(config('reference_data.complexion_list', []), $pp?->complexion)" :selected="$pp?->complexion ?? []" />
        <x-multi-select name="body_type" label="Body Type" :options="$mergeWithSelected(config('reference_data.body_type_list', []), $pp?->body_type)" :selected="$pp?->body_type ?? []" />
        <x-multi-select name="physical_status" label="Physical Status" :options="$mergeWithSelected(config('reference_data.physical_status_list', []), $pp?->physical_status)" :selected="$pp?->physical_status ?? []" />
        <x-multi-select name="family_status" label="Family Status" :options="$mergeWithSelected(config('reference_data.family_status_list', []), $pp?->family_status)" :selected="$pp?->family_status ?? []" />
        <x-multi-select name="religions" label="Religion" :options="$mergeWithSelected(config('reference_data.religion_list', []), $pp?->religions)" :selected="$pp?->religions ?? []" />
        <x-multi-select name="mother_tongues" label="Mother Tongue" :options="$mergeWithSelected(config('reference_data.language_list', []), $pp?->mother_tongues)" :selected="$pp?->mother_tongues ?? []" :searchable="true" />
        <x-multi-select name="education_levels" label="Education Level" :options="config('reference_data.educational_qualifications_list', [])" :selected="$pp?->education_levels ?? []" :searchable="true" :grouped="true" />
        <x-multi-select name="occupations" label="Occupation" :options="config('reference_data.occupation_category_list', [])" :selected="$pp?->occupations ?? []" :searchable="true" :grouped="true" />

        {{-- Location preference. Working location is the PRIMARY filter for this
             community — native state is near-uniform, so it's weighted lower in
             match scoring. All are "open to" multi-selects (pick any number);
             districts are grouped by state, the same data as registration. --}}
        <x-multi-select name="working_countries" label="Preferred Working Country(ies)" :options="config('reference_data.country_list', [])" :selected="$pp?->working_countries ?? []" :searchable="true" :grouped="true" />
        <x-multi-select name="working_states" label="Preferred Working State(s)" :options="config('locations.indian_states', [])" :selected="$pp?->working_states ?? []" :searchable="true" />
        <x-multi-select name="working_districts" label="Preferred Working District(s)" :options="config('locations.state_district_map', [])" :selected="$pp?->working_districts ?? []" :searchable="true" :grouped="true" />
        <x-multi-select name="native_districts" label="Preferred Native District(s)" :options="config('locations.state_district_map', [])" :selected="$pp?->native_districts ?? []" :searchable="true" :grouped="true" />
    </div>

    <div class="mt-5 float-field">
        <textarea name="about_partner" rows="4" maxlength="5000" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $pp?->about_partner ?? '' }}</textarea>
        <label>About Partner Expectations</label>
    </div>

    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
