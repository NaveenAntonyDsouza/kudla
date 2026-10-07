<?php

/*
| Pending interests sat unanswered until they expired (464 expired on kudla,
| Oct 2026). The dashboard now leads with "N members are waiting for your
| reply", and the inbox lets a member accept or decline from the list.
*/

it('leads the dashboard with the members waiting for a reply', function () {
    $view = file_get_contents(resource_path('views/dashboard/index.blade.php'));

    expect($view)->toContain("@if((\$interestStats['received'] ?? 0) > 0)")
        ->toContain('waiting for your reply')
        ->toContain("route('interests.inbox', ['tab' => 'received'])")
        // the 30-day warning only where expiry actually runs
        ->toContain("getValue('interest_expiry_enabled', '0') === '1' ? ' — unanswered interests expire after 30 days'");

    // above the profile-completion CTA and recommended matches
    expect(strpos($view, 'interests-waiting-banner'))->toBeLessThan(strpos($view, 'INCOMPLETE PROFILE'));
});

it('lets the receiver accept or decline a pending interest from the inbox list', function () {
    $view = file_get_contents(resource_path('views/interests/inbox.blade.php'));

    expect($view)->toContain("@if(! \$isSender && \$interest->status === 'pending')")
        ->toContain("route('interests.accept', \$interest)")
        ->toContain('name="template_id" value="reply_accept_1"')
        ->toContain("route('interests.decline', \$interest)")
        ->toContain('name="template_id" value="decline_standard"')
        ->toContain("onsubmit=\"return confirm('Decline this interest?");

    // the buttons sit outside the row link, so a tap doesn't open the interest
    $row = substr($view, strpos($view, '<a href="{{ route(\'interests.show\', $interest) }}"'));
    expect(strpos($row, '</a>'))->toBeLessThan(strpos($row, 'interest-quick-reply'));
});
