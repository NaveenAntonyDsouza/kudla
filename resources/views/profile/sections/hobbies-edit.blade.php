@php $h = $profile->lifestyleInfo; @endphp
<form method="POST" action="{{ route('profile.update', 'hobbies') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field">
            <select name="diet"><option value="">Select</option>
                @foreach(['Vegetarian', 'Non-Vegetarian', 'Eggetarian', 'Vegan'] as $opt)
                    <option value="{{ $opt }}" {{ ($h?->diet ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Diet</label>
        </div>
        <div class="float-field">
            <select name="smoking"><option value="">Select</option>
                @foreach(['No', 'Occasionally', 'Yes'] as $opt)
                    <option value="{{ $opt }}" {{ ($h?->smoking ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Smoking</label>
        </div>
        <div class="float-field">
            <select name="drinking"><option value="">Select</option>
                @foreach(['No', 'Occasionally', 'Yes'] as $opt)
                    <option value="{{ $opt }}" {{ ($h?->drinking ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select><label>Drinking</label>
        </div>
        <div class="float-field"><input type="text" name="cultural_background" value="{{ $h?->cultural_background ?? '' }}" maxlength="30" placeholder=" "><label>Cultural Background</label></div>
    </div>

    <div class="space-y-4 mt-5">
        <x-multi-select name="hobbies" label="Hobbies" :options="config('reference_data.hobbies_list', [])" :selected="$h?->hobbies ?? []" :searchable="true" :showAny="false" />
        <x-multi-select name="favorite_music" label="Favorite Music" :options="config('reference_data.music_list', [])" :selected="$h?->favorite_music ?? []" :showAny="false" />
        <x-multi-select name="preferred_books" label="Preferred Books" :options="config('reference_data.books_list', [])" :selected="$h?->preferred_books ?? []" :showAny="false" />
        <x-multi-select name="preferred_movies" label="Preferred Movies" :options="config('reference_data.movies_list', [])" :selected="$h?->preferred_movies ?? []" :showAny="false" />
        <x-multi-select name="sports_fitness_games" label="Sports / Fitness / Games" :options="config('reference_data.sports_list', [])" :selected="$h?->sports_fitness_games ?? []" :showAny="false" />
        <x-multi-select name="favorite_cuisine" label="Favorite Cuisine" :options="config('reference_data.cuisine_list', [])" :selected="$h?->favorite_cuisine ?? []" :showAny="false" />
    </div>

    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
