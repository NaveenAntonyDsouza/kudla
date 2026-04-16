@php $p = $profile; $lang = $profile->lifestyleInfo?->languages_known ?? []; @endphp
<form method="POST" action="{{ route('profile.update', 'primary') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {{-- Read-only fields --}}
        <div class="float-field"><input type="text" value="{{ $p->full_name }}" readonly class="bg-gray-50" placeholder=" "><label>Full Name</label></div>
        <div class="float-field"><input type="text" value="{{ $p->gender }}" readonly class="bg-gray-50" placeholder=" "><label>Gender</label></div>
        <div class="float-field"><input type="text" value="{{ $p->date_of_birth?->format('d/m/Y') }}" readonly class="bg-gray-50" placeholder=" "><label>Date of Birth</label></div>
        <div class="float-field"><input type="text" value="{{ $p->age ? $p->age . ' years' : '' }}" readonly class="bg-gray-50" placeholder=" "><label>Age</label></div>
        <div class="float-field"><input type="text" value="{{ $p->height }}" readonly class="bg-gray-50" placeholder=" "><label>Height</label></div>
        <div class="float-field"><input type="text" value="{{ $p->marital_status }}" readonly class="bg-gray-50" placeholder=" "><label>Marital Status</label></div>

        {{-- Editable fields --}}
        <div class="float-field">
            <select name="weight_kg">
                <option value="">Select</option>
                @foreach(config('reference_data.weight_list', []) as $w)
                    <option value="{{ $w }}" {{ ($p->weight_kg ?? '') === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>
            <label>Weight</label>
        </div>
        <div class="float-field">
            <select name="complexion">
                <option value="">Select</option>
                @foreach(['Very Fair', 'Fair', 'Medium', 'Wheatish', 'Dark'] as $opt)
                    <option value="{{ $opt }}" {{ ($p->complexion ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Complexion</label>
        </div>
        <div class="float-field">
            <select name="body_type">
                <option value="">Select</option>
                @foreach(['Slim', 'Average', 'Athletic', 'Heavy'] as $opt)
                    <option value="{{ $opt }}" {{ ($p->body_type ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Body Type</label>
        </div>
        <div class="float-field">
            <select name="blood_group">
                <option value="">Select</option>
                @foreach(['A+ve', 'A-ve', 'B+ve', 'B-ve', 'AB+ve', 'AB-ve', 'O+ve', 'O-ve', 'Not Known'] as $opt)
                    <option value="{{ $opt }}" {{ ($p->blood_group ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Blood Group</label>
        </div>
        <div class="float-field">
            <select name="mother_tongue" required>
                <option value="">Select</option>
                @foreach(config('reference_data.language_list', []) as $opt)
                    <option value="{{ $opt }}" {{ ($p->mother_tongue ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Mother Tongue <span class="text-red-500">*</span></label>
        </div>
    </div>

    {{-- Languages Known (multi-select) --}}
    <div class="mt-5">
        <x-multi-select name="languages_known" label="Languages Known" :options="config('reference_data.language_list', [])" :selected="$lang" :searchable="true" :showAny="false" />
    </div>

    {{-- About Me --}}
    <div class="mt-5 float-field">
        <textarea name="about_me" rows="4" maxlength="5000" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $p->about_me ?? '' }}</textarea>
        <label>About the Candidate</label>
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
            class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
