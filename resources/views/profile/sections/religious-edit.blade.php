@php $r = $profile->religiousInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'religious') }}" enctype="multipart/form-data" @submit="submitting = true" x-data="{
    submitting: false,
    religion: '{{ $r?->religion ?? '' }}',
    communities: [],
    subCommunities: [],
    selectedCaste: '{{ $r?->caste ?? '' }}',
    otherCasteName: '{{ $r?->other_caste_name ?? '' }}',
    subCasteChoice: '',
    subCasteOther: '',
    savedSubCaste: '{{ $r?->sub_caste ?? '' }}',

    async fetchCommunities(preserve = false) {
        const keepCaste = this.selectedCaste;
        if (!this.religion || this.religion === 'Other' || this.religion === 'No Religion') {
            this.communities = []; this.subCommunities = [];
            this.selectedCaste = ''; this.resetSubCaste();
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
        const community = this.communities.find(c => c.community_name === this.selectedCaste);
        this.subCommunities = community ? (community.sub_communities || []) : [];
        if (preserve && this.savedSubCaste) {
            // Map the saved sub-caste onto the control: a listed value selects
            // it; an unlisted value routes to the 'Other' free-text box so it
            // survives and stays editable (no silent loss on save).
            if (this.subCommunities.includes(this.savedSubCaste)) {
                this.subCasteChoice = this.savedSubCaste; this.subCasteOther = '';
            } else {
                this.subCasteChoice = '__other__'; this.subCasteOther = this.savedSubCaste;
            }
        } else {
            this.resetSubCaste();
        }
    },

    resetSubCaste() { this.subCasteChoice = ''; this.subCasteOther = ''; },

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

        {{-- Christian fields. Diocese cascades from Denomination by rite via
             /api/cascade/dioceses (Latin / Syro-Malabar / Syro-Malankara);
             "Other (not listed)" reveals a free-text box — also the path for
             Non-Catholic denominations. Denomination preserves a stored value
             not in its list; the saved diocese is preserved via "Other" if it
             falls outside the chosen rite. --}}
        <template x-if="religion === 'Christian'">
            <div class="contents" x-data="{
                selectedDenomination: '{{ $r?->denomination ?? '' }}',
                otherDenominationName: '{{ $r?->other_denomination_name ?? '' }}',
                dioceses: [],
                dioceseChoice: '',
                dioceseOther: '',
                savedDiocese: '{{ $r?->diocese ?? '' }}',
                savedDioceseName: '{{ $r?->diocese_name ?? '' }}',

                async fetchDioceses(preserve = false) {
                    if (!this.selectedDenomination) {
                        this.dioceses = [];
                        if (!preserve) { this.dioceseChoice = ''; this.dioceseOther = ''; }
                        return;
                    }
                    try {
                        const res = await fetch('/api/cascade/dioceses?denomination=' + encodeURIComponent(this.selectedDenomination));
                        this.dioceses = await res.json();
                    } catch (e) { this.dioceses = []; }
                    if (preserve) {
                        const saved = this.savedDiocese;
                        if (saved && saved !== 'Other' && this.dioceses.includes(saved)) {
                            this.dioceseChoice = saved; this.dioceseOther = '';
                        } else if (saved === 'Other' || this.savedDioceseName || saved) {
                            this.dioceseChoice = '__other__';
                            this.dioceseOther = this.savedDioceseName || (saved !== 'Other' ? saved : '');
                        } else {
                            this.dioceseChoice = ''; this.dioceseOther = '';
                        }
                    } else {
                        this.dioceseChoice = ''; this.dioceseOther = '';
                    }
                },

                init() { if (this.selectedDenomination) this.fetchDioceses(true); }
            }">
                @php $denomFlat = collect(config('reference_data.denomination_list', []))->flatten()->all(); @endphp
                <div class="float-field">
                    <select name="denomination" x-model="selectedDenomination" @change="fetchDioceses()"><option value="">Select</option>
                        @foreach(config('reference_data.denomination_list', []) as $group => $items)
                            @if(is_array($items))
                                <optgroup label="{{ $group }}">
                                    @foreach($items as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </optgroup>
                            @else
                                <option value="{{ $items }}">{{ $items }}</option>
                            @endif
                        @endforeach
                        @if(($r?->denomination ?? '') !== '' && ! in_array($r?->denomination, $denomFlat, true))
                            <optgroup label="Current"><option value="{{ $r->denomination }}">{{ $r->denomination }}</option></optgroup>
                        @endif
                    </select><label>Denomination</label>
                </div>
                {{-- Specify when "Other" picked (Catholic or Non-Catholic Other). --}}
                <div class="float-field" x-show="selectedDenomination === 'Other'" x-transition>
                    <input type="text" name="other_denomination_name" x-model="otherDenominationName" maxlength="100" placeholder=" ">
                    <label>Specify Denomination</label>
                </div>
                {{-- Diocese cascade — shown once a real (non-"Other") denomination is picked.
                     Admin-toggleable (General Settings → Registration & Approval → "Show Diocese field"). --}}
                @if(\App\Models\SiteSetting::getValue('show_diocese', '1') === '1')
                <template x-if="selectedDenomination && selectedDenomination !== 'Other'">
                    <div class="contents">
                        <div class="float-field">
                            <select x-model="dioceseChoice"><option value="">Select</option>
                                <template x-for="d in dioceses" :key="d">
                                    <option :value="d" x-text="d"></option>
                                </template>
                                <option value="__other__">Other (not listed)</option>
                            </select><label>Diocese</label>
                        </div>
                        <div class="float-field" x-show="dioceseChoice === '__other__'" x-transition>
                            <input type="text" x-model="dioceseOther" maxlength="100" placeholder=" ">
                            <label>Diocese Name</label>
                        </div>
                        <input type="hidden" name="diocese" :value="dioceseChoice === '__other__' ? 'Other' : dioceseChoice">
                        <input type="hidden" name="diocese_name" :value="dioceseChoice === '__other__' ? dioceseOther : ''">
                    </div>
                </template>
                @endif
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
                {{-- Caste / Community + Sub-Caste apply to Hindu only; Jains pick
                     a Sect below. x-if removes the required <select> from the DOM
                     for Jain so it can't block submission. --}}
                <template x-if="religion === 'Hindu'">
                    <div class="contents">
                        <div class="float-field">
                            <select name="caste" x-model="selectedCaste" @change="loadSubCommunities()" required>
                                <option value="">Select</option>
                                <template x-for="community in communities" :key="community.id">
                                    <option :value="community.community_name" x-text="community.community_name" :selected="community.community_name === selectedCaste"></option>
                                </template>
                            </select>
                            <label>Caste / Community</label>
                        </div>
                        {{-- "Other / Not Listed" → show ONLY this box (no sub-caste). --}}
                        <div class="float-field" x-show="['Other / Not Listed', 'Other (not listed)', 'Other'].includes(selectedCaste)" x-transition>
                            <input type="text" name="other_caste_name" x-model="otherCasteName" maxlength="100" placeholder=" ">
                            <label>Specify Caste / Community</label>
                        </div>
                        {{-- Sub-Caste: only for a real (non-"Other") caste. Options
                             from the chosen community's sub-communities + an "Other"
                             escape hatch. The <select> is UI-only; the hidden input
                             carries the real value (an unlisted saved value
                             auto-routes to "Other" pre-filled — never lost). --}}
                        <template x-if="selectedCaste && !['Other / Not Listed', 'Other (not listed)', 'Other'].includes(selectedCaste)">
                            <div class="contents">
                                <div class="float-field">
                                    <select x-model="subCasteChoice">
                                        <option value="">Select</option>
                                        <template x-for="sub in subCommunities" :key="sub">
                                            <option :value="sub" x-text="sub"></option>
                                        </template>
                                        <option value="__other__">Other (not listed)</option>
                                    </select>
                                    <label>Sub Caste</label>
                                </div>
                                <div class="float-field" x-show="subCasteChoice === '__other__'" x-transition>
                                    <input type="text" x-model="subCasteOther" maxlength="50" placeholder=" ">
                                    <label>Enter Sub-Caste</label>
                                </div>
                                <input type="hidden" name="sub_caste" :value="subCasteChoice === '__other__' ? subCasteOther : subCasteChoice">
                            </div>
                        </template>
                    </div>
                </template>
                <div class="float-field">
                    @php $gotraOpts = config('reference_data.gothram_list', []); @endphp
                    <select name="gotra"><option value="">Select</option>
                        @foreach($gotraOpts as $opt)
                            <option value="{{ $opt }}" {{ ($r?->gotra ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                        @if(($r?->gotra ?? '') !== '' && ! in_array($r?->gotra, $gotraOpts, true))
                            <option value="{{ $r->gotra }}" selected>{{ $r->gotra }}</option>
                        @endif
                    </select><label>Gotra / Gothram</label>
                </div>
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
                {{-- Jain Sect — shown for Jain only. --}}
                <div class="float-field" x-show="religion === 'Jain'" x-transition>
                    @php $jainOpts = config('reference_data.jain_sect_list', []); @endphp
                    <select name="jain_sect"><option value="">Select</option>
                        @foreach($jainOpts as $opt)
                            <option value="{{ $opt }}" {{ ($r?->jain_sect ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                        @if(($r?->jain_sect ?? '') !== '' && ! in_array($r?->jain_sect, $jainOpts, true))
                            <option value="{{ $r->jain_sect }}" selected>{{ $r->jain_sect }}</option>
                        @endif
                    </select><label>Jain Sect</label>
                </div>
            </div>
        </template>

        {{-- Muslim fields — dropdowns from config (matches registration + admin),
             each preserving a stored value not in its list. --}}
        <template x-if="religion === 'Muslim'">
            <div class="contents">
                @php
                    $sectOpts = config('reference_data.muslim_sect_list', []);
                    $jamathOpts = config('reference_data.jamath_list', []);
                    $observanceOpts = config('reference_data.religious_observance_list', []);
                @endphp
                <div class="float-field">
                    <select name="muslim_sect"><option value="">Select</option>
                        @foreach($sectOpts as $opt)
                            <option value="{{ $opt }}" {{ ($r?->muslim_sect ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                        @if(($r?->muslim_sect ?? '') !== '' && ! in_array($r?->muslim_sect, $sectOpts, true))
                            <option value="{{ $r->muslim_sect }}" selected>{{ $r->muslim_sect }}</option>
                        @endif
                    </select><label>Muslim Sect</label>
                </div>
                <div class="float-field">
                    <select name="muslim_community"><option value="">Select</option>
                        @foreach($jamathOpts as $opt)
                            <option value="{{ $opt }}" {{ ($r?->muslim_community ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                        @if(($r?->muslim_community ?? '') !== '' && ! in_array($r?->muslim_community, $jamathOpts, true))
                            <option value="{{ $r->muslim_community }}" selected>{{ $r->muslim_community }}</option>
                        @endif
                    </select><label>Community / Jamath</label>
                </div>
                <div class="float-field">
                    <select name="religious_observance"><option value="">Select</option>
                        @foreach($observanceOpts as $opt)
                            <option value="{{ $opt }}" {{ ($r?->religious_observance ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                        @if(($r?->religious_observance ?? '') !== '' && ! in_array($r?->religious_observance, $observanceOpts, true))
                            <option value="{{ $r->religious_observance }}" selected>{{ $r->religious_observance }}</option>
                        @endif
                    </select><label>Religious Observance</label>
                </div>
            </div>
        </template>

        {{-- Other religion — free-text name, shown only when "Other". --}}
        <template x-if="religion === 'Other'">
            <div class="contents">
                <div class="float-field">
                    <input type="text" name="other_religion_name" value="{{ $r?->other_religion_name ?? '' }}" placeholder=" ">
                    <label>Specify Religion</label>
                </div>
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
