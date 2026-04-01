@php $r = $profile->religiousInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'religious') }}" enctype="multipart/form-data" x-data="{ submitting: false, religion: '{{ $r?->religion ?? '' }}' }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            <select name="religion" x-model="religion" required>
                <option value="">Select</option>
                @foreach(['Christian', 'Hindu', 'Muslim', 'Jain', 'Sikh', 'Buddhist', 'Other'] as $opt)
                    <option value="{{ $opt }}" {{ ($r?->religion ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <label>Religion <span class="text-red-500">*</span></label>
        </div>

        {{-- Christian fields --}}
        <template x-if="religion === 'Christian'">
            <div class="contents">
                <div class="float-field">
                    <select name="denomination"><option value="">Select</option>
                        @foreach(config('reference_data.denomination_list', []) as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach($items as $opt)
                                    <option value="{{ $opt }}" {{ ($r?->denomination ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select><label>Denomination</label>
                </div>
                <div class="float-field"><input type="text" name="diocese_name" value="{{ $r?->diocese_name ?? $r?->diocese ?? '' }}" placeholder=" "><label>Diocese</label></div>
                <div class="float-field"><input type="text" name="parish_name_place" value="{{ $r?->parish_name_place ?? '' }}" placeholder=" "><label>Parish Name & Place</label></div>
            </div>
        </template>

        {{-- Hindu/Jain fields --}}
        <template x-if="religion === 'Hindu' || religion === 'Jain'">
            <div class="contents">
                <div class="float-field"><input type="text" name="caste" value="{{ $r?->caste ?? '' }}" placeholder=" "><label>Caste</label></div>
                <div class="float-field"><input type="text" name="sub_caste" value="{{ $r?->sub_caste ?? '' }}" placeholder=" "><label>Sub Caste</label></div>
                <div class="float-field"><input type="text" name="gotra" value="{{ $r?->gotra ?? '' }}" placeholder=" "><label>Gotra</label></div>
                <div class="float-field">
                    <select name="nakshatra"><option value="">Select</option>
                        @foreach(config('reference_data.nakshatra_list', []) as $opt)
                            <option value="{{ $opt }}" {{ ($r?->nakshatra ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select><label>Nakshatra</label>
                </div>
                <div class="float-field">
                    <select name="rashi"><option value="">Select</option>
                        @foreach(config('reference_data.rasi_list', []) as $opt)
                            <option value="{{ $opt }}" {{ ($r?->rashi ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select><label>Rashi</label>
                </div>
                <div class="float-field">
                    <select name="manglik"><option value="">Select</option>
                        @foreach(['Yes', 'No', "Don't Know"] as $opt)
                            <option value="{{ $opt }}" {{ ($r?->dosh ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select><label>Manglik / Chovva Dosham</label>
                </div>
            </div>
        </template>

        {{-- Muslim fields --}}
        <template x-if="religion === 'Muslim'">
            <div class="contents">
                <div class="float-field"><input type="text" name="muslim_sect" value="{{ $r?->muslim_sect ?? '' }}" placeholder=" "><label>Muslim Sect</label></div>
                <div class="float-field"><input type="text" name="muslim_community" value="{{ $r?->muslim_community ?? '' }}" placeholder=" "><label>Muslim Community</label></div>
            </div>
        </template>

        <div class="float-field"><input type="time" name="time_of_birth" value="{{ $r?->time_of_birth ?? '' }}" placeholder=" "><label>Time of Birth</label></div>
        <div class="float-field"><input type="text" name="place_of_birth" value="{{ $r?->place_of_birth ?? '' }}" placeholder=" "><label>Place of Birth</label></div>
    </div>

    {{-- Jathakam upload --}}
    <div class="mt-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">Jathakam / Horoscope</label>
        @if($r?->jathakam_upload_url)
            <div class="mb-2 flex items-center gap-3 p-2 bg-green-50 border border-green-200 rounded-lg">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm text-green-700">Uploaded</span>
                <a href="{{ Storage::disk('public')->url($r->jathakam_upload_url) }}" target="_blank" class="text-sm text-(--color-primary) hover:underline font-medium ml-auto">View</a>
            </div>
        @endif
        <input type="file" name="jathakam" accept=".jpg,.jpeg,.png,.pdf"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-(--color-primary)/10 file:text-(--color-primary)">
        <p class="mt-1 text-xs text-gray-500">JPG, PNG or PDF (max 2MB)</p>
    </div>

    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
            class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
