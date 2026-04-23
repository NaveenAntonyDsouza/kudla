<x-layouts.onboarding title="Lifestyle & Social Media" :step="4" :completionPct="$completionPct">

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif

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

    <form method="POST" action="{{ route('onboarding.storeLifestyle') }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        {{-- ── Lifestyle & Habits ────────────────────────────── --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Lifestyle & Habits</h2>
            <a href="#" @click.prevent="document.getElementById('skip-form-top').submit()" class="text-sm text-(--color-primary) hover:underline font-medium">Skip for now &rarr;</a>
        </div>

        <div class="space-y-5 mb-10">
            {{-- Eating Habits --}}
            <div class="float-field">
                <select name="diet" id="diet">
                    <option value="">Select</option>
                    @foreach(config('reference_data.eating_habits') as $opt)
                        <option value="{{ $opt }}" {{ old('diet', $lifestyle?->diet ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="diet">Eating Habits</label>
                @error('diet') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Drinking Habits --}}
            <div class="float-field">
                <select name="drinking" id="drinking">
                    <option value="">Select</option>
                    @foreach(config('reference_data.drinking_habits') as $opt)
                        <option value="{{ $opt }}" {{ old('drinking', $lifestyle?->drinking ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="drinking">Drinking Habits</label>
                @error('drinking') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Smoking Habits --}}
            <div class="float-field">
                <select name="smoking" id="smoking">
                    <option value="">Select</option>
                    @foreach(config('reference_data.smoking_habits') as $opt)
                        <option value="{{ $opt }}" {{ old('smoking', $lifestyle?->smoking ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="smoking">Smoking Habits</label>
                @error('smoking') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Cultural Background --}}
            <div class="float-field">
                <select name="cultural_background" id="cultural_background">
                    <option value="">Select</option>
                    @foreach(config('reference_data.cultural_background_list') as $opt)
                        <option value="{{ $opt }}" {{ old('cultural_background', $lifestyle?->cultural_background ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <label for="cultural_background">Cultural Background</label>
                @error('cultural_background') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- ── Hobbies & Interests ───────────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Hobbies & Interests</h2>

        <div class="space-y-5 mb-10">
            <x-multi-select name="hobbies" label="Hobbies"
                :options="config('reference_data.hobbies_list')" :searchable="true"
                :selected="$lifestyle?->hobbies ?? []" />

            <x-multi-select name="favorite_music" label="Favorite Music"
                :options="config('reference_data.music_list')"
                :selected="$lifestyle?->favorite_music ?? []" />

            <x-multi-select name="preferred_books" label="Preferred Books"
                :options="config('reference_data.books_list')"
                :selected="$lifestyle?->preferred_books ?? []" />

            <x-multi-select name="preferred_movies" label="Preferred Movies"
                :options="config('reference_data.movies_list')"
                :selected="$lifestyle?->preferred_movies ?? []" />

            <x-multi-select name="sports_fitness_games" label="Sports / Fitness / Games"
                :options="config('reference_data.sports_list')" :searchable="true"
                :selected="$lifestyle?->sports_fitness_games ?? []" />

            <x-multi-select name="favorite_cuisine" label="Favorite Cuisine"
                :options="config('reference_data.cuisine_list')"
                :selected="$lifestyle?->favorite_cuisine ?? []" />

        </div>

        {{-- ── Social Media Information ──────────────────────── --}}
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Social Media Information</h2>

        <div class="space-y-5 mb-10">
            <div class="float-field">
                <input type="url" name="facebook_url" id="facebook_url" value="{{ old('facebook_url', $socialMedia?->facebook_url ?? '') }}" maxlength="200" placeholder=" ">
                <label for="facebook_url">Facebook</label>
                @error('facebook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="url" name="instagram_url" id="instagram_url" value="{{ old('instagram_url', $socialMedia?->instagram_url ?? '') }}" maxlength="200" placeholder=" ">
                <label for="instagram_url">Instagram</label>
                @error('instagram_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $socialMedia?->linkedin_url ?? '') }}" maxlength="200" placeholder=" ">
                <label for="linkedin_url">LinkedIn</label>
                @error('linkedin_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="url" name="youtube_url" id="youtube_url" value="{{ old('youtube_url', $socialMedia?->youtube_url ?? '') }}" maxlength="200" placeholder=" ">
                <label for="youtube_url">YouTube</label>
                @error('youtube_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="float-field">
                <input type="url" name="website_url" id="website_url" value="{{ old('website_url', $socialMedia?->website_url ?? '') }}" maxlength="200" placeholder=" ">
                <label for="website_url">Website</label>
                @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Navigation --}}
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-6 border-t border-gray-200">
            <a href="{{ route('onboarding.preferences') }}"
                class="w-full sm:w-auto text-center border border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-800 rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors">
                Back
            </a>
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <form action="{{ route('onboarding.finish') }}" method="POST" class="order-2 sm:order-1">
                    @csrf
                    <button type="submit" class="text-sm text-(--color-primary) hover:underline font-medium">Skip for now</button>
                </form>
                <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'"
                    class="w-full sm:w-auto bg-(--color-primary) text-white hover:bg-(--color-primary-hover) rounded-lg px-8 py-3 font-semibold text-sm uppercase tracking-wider transition-colors order-1 sm:order-2">
                    <span x-show="!submitting">Save</span>
                    <span x-show="submitting" x-cloak>Please wait...</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Hidden skip form (outside main form to avoid nesting) --}}
    <form id="skip-form-top" action="{{ route('onboarding.finish') }}" method="POST" class="hidden">
        @csrf
    </form>
</x-layouts.onboarding>
