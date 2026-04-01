@php $h = $profile->lifestyleInfo; @endphp
@if(!$h)
    <p class="text-sm text-gray-400">No hobbies & interests added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Diet</p><p class="text-sm font-medium text-gray-900">{{ $h->diet ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Smoking</p><p class="text-sm font-medium text-gray-900">{{ $h->smoking ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Drinking</p><p class="text-sm font-medium text-gray-900">{{ $h->drinking ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Cultural Background</p><p class="text-sm font-medium text-gray-900">{{ $h->cultural_background ?? 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Hobbies</p><p class="text-sm font-medium text-gray-900">{{ $h->hobbies && count($h->hobbies) ? implode(', ', $h->hobbies) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Favorite Music</p><p class="text-sm font-medium text-gray-900">{{ $h->favorite_music && count($h->favorite_music) ? implode(', ', $h->favorite_music) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Preferred Books</p><p class="text-sm font-medium text-gray-900">{{ $h->preferred_books && count($h->preferred_books) ? implode(', ', $h->preferred_books) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Preferred Movies</p><p class="text-sm font-medium text-gray-900">{{ $h->preferred_movies && count($h->preferred_movies) ? implode(', ', $h->preferred_movies) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Sports / Fitness / Games</p><p class="text-sm font-medium text-gray-900">{{ $h->sports_fitness_games && count($h->sports_fitness_games) ? implode(', ', $h->sports_fitness_games) : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Favorite Cuisine</p><p class="text-sm font-medium text-gray-900">{{ $h->favorite_cuisine && count($h->favorite_cuisine) ? implode(', ', $h->favorite_cuisine) : 'Not Mentioned' }}</p></div>
</div>
@endif
