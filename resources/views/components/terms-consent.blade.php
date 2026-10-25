@props(['id' => 'accept_terms'])
{{-- Sign-up consent (Oct 2026): a clear tick, not "by clicking you agree"; the time is stored in users.terms_accepted_at --}}
<div style="margin-top: 14px;">
    <label for="{{ $id }}" style="display: flex; gap: 8px; align-items: flex-start; font-size: 12.5px; line-height: 1.45; color: #4b5563; text-align: left; cursor: pointer;">
        <input type="checkbox" name="accept_terms" id="{{ $id }}" value="1" required @checked(old('accept_terms'))
            style="flex: none; width: 16px; height: 16px; margin-top: 1px; accent-color: var(--color-primary, #8B1D91);">
        <span>I agree to the <a href="{{ route('privacy') }}" target="_blank" class="text-(--color-primary) hover:underline">Privacy Policy</a> and <a href="{{ route('terms') }}" target="_blank" class="text-(--color-primary) hover:underline">Terms of Use</a>.</span>
    </label>
    @error('accept_terms') <p style="margin: 4px 0 0; font-size: 12px; color: #dc2626;">{{ $message }}</p> @enderror
</div>
