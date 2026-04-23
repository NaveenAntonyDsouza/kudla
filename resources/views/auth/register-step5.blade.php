<x-layouts.registration title="Step 5 - Profile Creation Details" :step="5">

    <h2 class="text-lg font-semibold text-gray-900 mb-6">Profile Creation Details</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below:</p>
            <ul class="mt-1 text-xs text-red-500 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store5') }}" x-data="{
        createdBy: '{{ old('created_by', $profile?->created_by ?? '') }}',
        userName: '{{ auth()->user()->name ?? '' }}',
        userPhone: '{{ auth()->user()->phone ?? '' }}',
        creatorName: '{{ old('creator_name', $profile?->creator_name ?? '') }}',
        creatorPhone: '{{ old('creator_contact_number', $profile?->creator_contact_number ?? '') }}',

        onCreatedByChange() {
            if (this.createdBy === 'Self / Candidate') {
                this.creatorName = this.userName;
                this.creatorPhone = this.userPhone;
            } else {
                if (this.creatorName === this.userName) this.creatorName = '';
                if (this.creatorPhone === this.userPhone) this.creatorPhone = '';
            }
        }
    }">
        @csrf

        <div class="space-y-5">
            {{-- Created By --}}
            <div class="float-field">
                <select name="created_by" id="created_by" x-model="createdBy" @change="onCreatedByChange()" required>
                    <option value="">Select</option>
                    @foreach(['Self / Candidate', 'Father', 'Mother', 'Brother', 'Sister', 'Friend', 'Relatives'] as $opt)
                        <option value="{{ $opt }}" {{ old('created_by', $profile?->created_by ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="created_by">Created By <span class="text-red-500">*</span></label>
                @error('created_by') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Creator Name (hidden when Self / Candidate) --}}
            <div x-show="createdBy && createdBy !== 'Self / Candidate'" x-transition class="float-field">
                <input type="text" name="creator_name" id="creator_name" x-model="creatorName"
                    :required="createdBy && createdBy !== 'Self / Candidate'" placeholder=" ">
                <label for="creator_name">Creator's Name <span class="text-red-500">*</span></label>
                @error('creator_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Creator Contact (hidden when Self / Candidate) --}}
            <div x-show="createdBy && createdBy !== 'Self / Candidate'" x-transition>
                <x-phone-input name="creator_contact_number" label="Contact Number" :value="$profile?->creator_contact_number ?? ''" xModel="creatorPhone" :required="true" />
            </div>

            {{-- Hidden fields for Self / Candidate --}}
            <template x-if="createdBy === 'Self / Candidate'">
                <div>
                    <input type="hidden" name="creator_name" :value="userName">
                    <input type="hidden" name="creator_contact_number" :value="userPhone">
                </div>
            </template>

            {{-- How did you hear about us --}}
            <div class="float-field">
                <select name="how_did_you_hear_about_us" id="how_did_you_hear_about_us">
                    <option value="">Select</option>
                    @foreach(config('reference_data.how_did_you_hear_list') as $group => $options)
                        <optgroup label="{{ $group }}">
                            @foreach($options as $opt)
                                <option value="{{ $opt }}" {{ old('how_did_you_hear_about_us', $profile?->how_did_you_hear_about_us ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <label for="how_did_you_hear_about_us">How Did You Hear About Us?</label>
                @error('how_did_you_hear_about_us') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('register.step4') }}"
                class="border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Finish
            </button>
        </div>
    </form>
</x-layouts.registration>
