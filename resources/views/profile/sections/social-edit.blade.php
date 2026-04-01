@php $s = $profile->socialMediaLink; @endphp
<form method="POST" action="{{ route('profile.update', 'social') }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="float-field"><input type="url" name="facebook_url" value="{{ $s?->facebook_url ?? '' }}" maxlength="200" placeholder=" "><label>Facebook URL</label></div>
        <div class="float-field"><input type="url" name="instagram_url" value="{{ $s?->instagram_url ?? '' }}" maxlength="200" placeholder=" "><label>Instagram URL</label></div>
        <div class="float-field"><input type="url" name="linkedin_url" value="{{ $s?->linkedin_url ?? '' }}" maxlength="200" placeholder=" "><label>LinkedIn URL</label></div>
        <div class="float-field"><input type="url" name="youtube_url" value="{{ $s?->youtube_url ?? '' }}" maxlength="200" placeholder=" "><label>YouTube URL</label></div>
        <div class="float-field"><input type="url" name="website_url" value="{{ $s?->website_url ?? '' }}" maxlength="200" placeholder=" "><label>Website URL</label></div>
    </div>
    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
        <button type="button" @click="editing = false" class="px-6 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
        <button type="submit" :disabled="submitting" :class="submitting && 'opacity-50 cursor-not-allowed'" class="px-6 py-2 text-sm font-semibold text-white bg-(--color-primary) hover:bg-(--color-primary-hover) rounded-lg">
            <span x-show="!submitting">Save</span><span x-show="submitting" x-cloak>Saving...</span>
        </button>
    </div>
</form>
