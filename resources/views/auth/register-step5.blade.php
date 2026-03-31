<x-layouts.auth title="Step 5 - Registration" maxWidth="xl">
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700">Step 5 of 5</span>
            <span class="text-sm text-gray-500">Profile Creation</span>
        </div>
        <div class="flex gap-1">
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
            <div class="h-2 flex-1 rounded-full bg-(--color-primary)"></div>
        </div>
    </div>

    <h2 class="text-xl font-serif font-bold text-gray-900 mb-6">Profile Creation Details</h2>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600 font-medium">Please fix the errors below.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store5') }}" x-data="{
        createdBy: '{{ old('created_by', '') }}'
    }">
        @csrf

        <div class="space-y-4">
            {{-- Created By --}}
            <div>
                <label for="created_by" class="block text-sm font-medium text-gray-700 mb-1">Profile Created By <span class="text-red-500">*</span></label>
                <select name="created_by" id="created_by" x-model="createdBy" required
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    <option value="">Select</option>
                    @foreach(['Self', 'Parent', 'Sibling', 'Relative', 'Friend', 'Guardian', 'Other'] as $opt)
                        <option value="{{ $opt }}" {{ old('created_by') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                @error('created_by') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Creator Name (shown when not Self) --}}
            <div x-show="createdBy && createdBy !== 'Self'" x-transition>
                <label for="creator_name" class="block text-sm font-medium text-gray-700 mb-1">Creator's Name</label>
                <input type="text" name="creator_name" id="creator_name" value="{{ old('creator_name') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                    placeholder="Name of person creating this profile">
                @error('creator_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Creator Contact (shown when not Self) --}}
            <div x-show="createdBy && createdBy !== 'Self'" x-transition>
                <label for="creator_contact_number" class="block text-sm font-medium text-gray-700 mb-1">Creator's Contact Number</label>
                <input type="tel" name="creator_contact_number" id="creator_contact_number" value="{{ old('creator_contact_number') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full"
                    placeholder="Phone number">
                @error('creator_contact_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- How did you hear about us --}}
            <div>
                <label for="how_did_you_hear_about_us" class="block text-sm font-medium text-gray-700 mb-1">How did you hear about us?</label>
                <select name="how_did_you_hear_about_us" id="how_did_you_hear_about_us"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-(--color-primary) focus:border-(--color-primary) w-full">
                    <option value="">Select</option>
                    @foreach(['Google Search', 'Facebook', 'Instagram', 'WhatsApp', 'Friend / Relative', 'Parish / Church', 'Newspaper', 'Marriage Bureau', 'Poster / Banner', 'Other'] as $opt)
                        <option value="{{ $opt }}" {{ old('how_did_you_hear_about_us') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                @error('how_did_you_hear_about_us') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Navigation ───────────────────────────────────── --}}
        <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
            <a href="{{ route('register.step4') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back</a>
            <button type="submit"
                class="bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-6 py-2.5 font-semibold text-sm transition-colors">
                Continue to Verification &rarr;
            </button>
        </div>
    </form>
</x-layouts.auth>
