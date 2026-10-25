{{-- "N members are waiting for your reply" (dashboard). $where = 'phone' (top of the page, above the profile card) or 'wide' (main column). --}}
<a href="{{ route('interests.inbox', ['tab' => 'received']) }}" class="interests-waiting-banner iw-{{ $where }}"
   style="align-items: center; gap: 12px; padding: 16px 20px; border-radius: 8px; background: #fffbeb; border: 1px solid #fcd34d; text-decoration: none;">
    <span style="display: flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 8px; border-radius: 9999px; background: #f59e0b; color: #fff; font-weight: 700; font-size: 15px;">{{ $interestStats['received'] }}</span>
    <span style="flex: 1; min-width: 0;">
        <span style="display: block; font-size: 14px; font-weight: 600; color: #92400e;">{{ $interestStats['received'] === 1 ? '1 member is' : $interestStats['received'].' members are' }} waiting for your reply</span>
        <span style="display: block; font-size: 12px; color: #b45309; margin-top: 2px;">Accept or decline in one tap{{ \App\Models\SiteSetting::getValue('interest_expiry_enabled', '0') === '1' ? ' — unanswered interests expire after 30 days' : '' }}.</span>
    </span>
    <span style="font-size: 13px; font-weight: 600; color: #b45309; white-space: nowrap;">Reply &rarr;</span>
</a>
