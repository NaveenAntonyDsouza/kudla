@php
    $p = $profile;
    $lang = $profile->lifestyleInfo?->languages_known ?? [];
    $da = $profile->differentlyAbledInfo;
    // A saved value that's no longer in a list stays selectable, so re-saving never blanks it.
    $withCurrent = fn (array $list, $current) => ($current !== null && $current !== '' && ! in_array($current, $list, true)) ? array_merge([$current], $list) : $list;
    $heights = $withCurrent(config('reference_data.height_list', []), $p->height);
    $maritals = $withCurrent(config('reference_data.marital_status_list', []), $p->marital_status);
    $physicals = $withCurrent(config('reference_data.physical_status_list', []), $p->physical_status);
    $helpPhone = \App\Support\SiteContact::phoneDisplay();
    $helpWa = \App\Support\SiteContact::whatsappHref();
    $locked = 'background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; padding-right: 36px;';
@endphp
<form method="POST" action="{{ route('profile.update', 'primary') }}" x-data="{ submitting: false, maritalStatus: @js($p->marital_status ?? ''), physicalStatus: @js($p->physical_status ?? ''), daCategory: @js($da?->category ?? '') }" @submit="submitting = true">
    @csrf
    {{-- Locked: only our team changes these (protects against fake or re-used profiles) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @foreach([
            ['Full Name', $p->full_name],
            ['Gender', $p->gender ? ucfirst($p->gender) : ''],
            ['Date of Birth', $p->date_of_birth?->format('d/m/Y')],
            ['Age', $p->age ? $p->age . ' years' : ''],
        ] as [$lockedLabel, $lockedValue])
            <div class="float-field">
                <input type="text" value="{{ $lockedValue }}" readonly tabindex="-1" aria-readonly="true" style="{{ $locked }}" placeholder=" ">
                <label>{{ $lockedLabel }}</label>
                <svg width="15" height="15" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" aria-hidden="true" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-500" style="margin-top: 8px; display: flex; gap: 6px; align-items: flex-start;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="flex: none; margin-top: 1px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <span>Name, gender and date of birth can only be changed by our team.
            @if($helpPhone)
                Call <a href="{{ \App\Support\SiteContact::phoneHref() }}" class="text-(--color-primary) hover:underline" style="white-space: nowrap;">{{ $helpPhone }}</a>@if($helpWa) or <a href="{{ $helpWa }}" target="_blank" rel="noopener" class="text-(--color-primary) hover:underline">WhatsApp us</a>@endif.
            @else
                <a href="/contact-us" class="text-(--color-primary) hover:underline">Contact us</a>.
            @endif
        </span>
    </p>

    {{-- Editable fields --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" style="margin-top: 20px;">
        <div class="float-field">
            <select name="height" required>
                <option value="">Select</option>
                @foreach($heights as $h)
                    <option value="{{ $h }}" {{ ($p->height ?? '') === $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
            </select>
            <label>Height <span class="text-red-500">*</span></label>
        </div>
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
            <select name="marital_status" x-model="maritalStatus" required>
                <option value="">Select</option>
                @foreach($maritals as $opt)
                    <option value="{{ $opt }}" {{ ($p->marital_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Marital Status <span class="text-red-500">*</span></label>
        </div>
        <div class="float-field">
            <select name="physical_status" x-model="physicalStatus">
                <option value="">Select</option>
                @foreach($physicals as $opt)
                    <option value="{{ $opt }}" {{ ($p->physical_status ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Physical Status</label>
        </div>
        <template x-if="maritalStatus && maritalStatus !== 'Unmarried'">
            <div class="grid grid-cols-2 gap-5" style="grid-column: 1 / -1;">
                <x-count-select name="children_with_me" label="Children with me" :value="$p->children_with_me" :max="6" />
                <x-count-select name="children_not_with_me" label="Children not with me" :value="$p->children_not_with_me" :max="6" />
            </div>
        </template>
        <div class="float-field" x-show="physicalStatus === 'Differently Abled'" x-transition>
            <select name="da_category" x-model="daCategory">
                <option value="">Select</option>
                @foreach(['Deaf & Dumb', 'Dwarfism', 'Hearing Impaired', 'Mentally Challenged', 'Physical Disability', 'Speech Impaired', 'Visually Challenged', 'Other'] as $opt)
                    <option value="{{ $opt }}" {{ ($da?->category ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Category of Differently Abled</label>
        </div>
        <div class="float-field" x-show="physicalStatus === 'Differently Abled' && daCategory === 'Other'" x-transition>
            <input type="text" name="da_category_other" value="{{ $da?->specify ?? '' }}" maxlength="50" placeholder=" ">
            <label>Specify Differently Abled</label>
        </div>
        <div class="float-field">
            <select name="complexion">
                <option value="">Select</option>
                @foreach(config('reference_data.complexion_list', []) as $opt)
                    <option value="{{ $opt }}" {{ ($p->complexion ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Complexion</label>
        </div>
        <div class="float-field">
            <select name="body_type">
                <option value="">Select</option>
                @foreach(config('reference_data.body_type_list', []) as $opt)
                    <option value="{{ $opt }}" {{ ($p->body_type ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Body Type</label>
        </div>
        <div class="float-field">
            <select name="blood_group">
                <option value="">Select</option>
                @foreach(config('reference_data.blood_group_list', []) as $opt)
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
    <div class="mt-5 float-field" x-show="physicalStatus === 'Differently Abled'" x-transition>
        <textarea name="da_description" rows="3" maxlength="500" placeholder=" " class="border border-gray-300 rounded-lg w-full focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary)">{{ $da?->description ?? '' }}</textarea>
        <label>Describe Differently Abled</label>
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
