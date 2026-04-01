@php $s = $profile->socialMediaLink; @endphp
@if(!$s)
    <p class="text-sm text-gray-400">No social media links added yet.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3">
    <div><p class="text-xs text-gray-500">Facebook</p><p class="text-sm font-medium text-gray-900 truncate">{{ $s->facebook_url ? $s->facebook_url : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Instagram</p><p class="text-sm font-medium text-gray-900 truncate">{{ $s->instagram_url ? $s->instagram_url : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">LinkedIn</p><p class="text-sm font-medium text-gray-900 truncate">{{ $s->linkedin_url ? $s->linkedin_url : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">YouTube</p><p class="text-sm font-medium text-gray-900 truncate">{{ $s->youtube_url ? $s->youtube_url : 'Not Mentioned' }}</p></div>
    <div><p class="text-xs text-gray-500">Website</p><p class="text-sm font-medium text-gray-900 truncate">{{ $s->website_url ? $s->website_url : 'Not Mentioned' }}</p></div>
</div>
@endif
