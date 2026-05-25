@php $r = $profile->religiousInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'religious') }}" enctype="multipart/form-data" @submit="submitting = true" x-data="{
    submitting: false,
    religion: '{{ $r?->religion ?? '' }}',
    communities: [],
    subCommunities: [],
    selectedCaste: '{{ $r?->caste ?? '' }}',
    selectedSubCaste: '{{ $r?->sub_caste ?? '' }}',

    async fetchCommunities(preserve = false) {
        const keepCaste = this.selectedCaste;
        if (!this.religion || this.religion === 'Other' || this.religion === 'No Religion') {
            this.communities = []; this.subCommunities = [];
            this.selectedCaste = ''; this.selectedSubCaste = '';
            return;
        }
        try {
            const res = await fetch('/api/cascade/communities?religion=' + encodeURIComponent(this.religion));
            this.communities = await res.json();
        } catch (e) { this.communities = []; }
        // Safety net: keep an existing caste even if it's no longer in the
        // managed Communities list, so a member never has their saved value
        // silently dropped by a closed dropdown on save.
        if (preserve && keepCaste && !this.communities.some(c => c.community_name === keepCaste)) {
            this.communities.push({ id: 'existing', community_name: keepCaste, sub_communities: [] });
        }
        this.selectedCaste = preserve ? keepCaste : '';
        this.loadSubCommunities(preserve);
    },

    loadSubCommunities(preserve = false) {
        const keepSub = this.selectedSubCaste;
        const community = this.communities.find(c => c.community_name === this.selectedCaste);
        this.subCommunities = community ? (community.sub_communities || []) : [];
        // Same safety net for an existing sub-caste not in the managed list.
        if (preserve && keepSub && !this.subCommunities.includes(keepSub)) {
            this.subCommunities.push(keepSub);
        }
        this.selectedSubCaste = preserve ? keepSub : '';
    },

    init() {
        if (this.religion) this.fetchCommunities(true);
    }
}">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            @php
                $religionOptions = config('reference_data.religion_list', []);
                $currentReligion = $r?->religion ?? '';
            @endphp
            <select name="religion" x-model="religion" @change="fetchCommunities()" required>
                <option value="">Select</option>
                @foreach($religionOptions as $opt)
                    <option value="{{ $opt }}" {{ $currentReligion === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
                {{-- Preserve the user's saved religion in the dropdown even
                     if the admin has deactivated it. Without this, deactivating
                     "Jain" would make 1 existing user's stored value invisible
                     in this select; they'd save and lose their value. The
                     "(no longer offered)" suffix makes it clear admins removed
                     it but doesn't lose the user's data. --}}
                @if($currentReligion && ! in_array($currentReligion, $religionOptions, true))
                    <option value="{{ $currentReligion }}" selected>{{ $currentReligion }} (no longer offered)</option>
                @endif
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
                {{-- Caste / Sub-Caste mirror the registration cascade: options
                     load from the admin-managed Communities table
                     (/api/cascade/communities), religion-filtered. preserve=true
                     on init keeps a member's saved value selectable even if it's
                     no longer in the managed list (no silent data-loss on save). --}}
                <div class="float-field">
                    <select name="caste" x-model="selectedCaste" @change="loadSubCommunities()" required>
                        <option value="">Select</option>
                        <template x-for="community in communities" :key="community.id">
                            <option :value="community.community_name" x-text="community.community_name" :selected="community.community_name === selectedCaste"></option>
                        </template>
                    </select>
                    <label>Caste / Community</label>
                </div>
                <div class="float-field" x-show="subCommunities.length > 0" x-transition>
                    <select name="sub_caste" x-model="selectedSubCaste">
                        <option value="">Select</option>
                        <template x-for="sub in subCommunities" :key="sub">
                            <option :value="sub" x-text="sub" :selected="sub === selectedSubCaste"></option>
                        </template>
                    </select>
                    <label>Sub Caste</label>
                </div>
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
