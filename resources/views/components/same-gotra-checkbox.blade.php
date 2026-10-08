{{-- Partner preference (Oct 2026): hide profiles of the member's own gotra /
     bari, which families treat as brother and sister. Off unless ticked. --}}
@props(['checked' => false])

<label style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 10px; cursor: pointer;">
    <input type="checkbox" name="exclude_same_gotra" value="1" @checked($checked)
        style="margin-top: 3px; width: 18px; height: 18px; accent-color: var(--color-primary, #8B1D91);">
    <span>
        <span style="display: block; font-size: 14px; font-weight: 600; color: #111827;">Don't show profiles with my gotra / bari</span>
        <span style="display: block; margin-top: 2px; font-size: 12.5px; line-height: 1.4; color: #6b7280;">Same gotra or bari counts as brother and sister in many families. Works when you've added yours under Religious Information.</span>
    </span>
</label>
