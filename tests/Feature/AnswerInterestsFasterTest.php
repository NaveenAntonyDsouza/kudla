<?php

/*
| Pending interests sat unanswered until they expired (464 expired on kudla,
| Oct 2026). The dashboard now leads with "N members are waiting for your
| reply", and the inbox lets a member accept or decline from the list.
*/

it('leads the dashboard with the members waiting for a reply', function () {
    $view = file_get_contents(resource_path('views/dashboard/index.blade.php'));
    $banner = file_get_contents(resource_path('views/dashboard/_waiting-banner.blade.php'));

    expect($view)->toContain("@if((\$interestStats['received'] ?? 0) > 0)")
        ->and($banner)->toContain('waiting for your reply')
        ->toContain("route('interests.inbox', ['tab' => 'received'])")
        // the 30-day warning only where expiry actually runs
        ->toContain("getValue('interest_expiry_enabled', '0') === '1' ? ' — unanswered interests expire after 30 days'");

    // on a phone: before the profile card (Oct 2026); on wide screens: above the
    // profile-completion CTA and recommended matches
    $phone = strpos($view, "@include('dashboard._waiting-banner', ['where' => 'phone'])");
    $wide = strpos($view, "@include('dashboard._waiting-banner', ['where' => 'wide'])");
    expect($phone)->toBeLessThan(strpos($view, 'Left Sidebar: Profile Summary'))
        ->and($wide)->toBeLessThan(strpos($view, 'INCOMPLETE PROFILE'))
        ->and($view)->toContain('@media (min-width: 1024px) { .iw-phone { display: none; } .iw-wide { display: flex; } }');
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

it('shows who sent it in full and says what accepting shares', function () {
    $inbox = file_get_contents(resource_path('views/interests/inbox.blade.php'));
    $show = file_get_contents(resource_path('views/interests/show.blade.php'));

    expect($inbox)->not->toContain('text-sm font-semibold text-gray-900 truncate')
        ->toContain('style="overflow-wrap: anywhere;">{{ $other?->matri_id')
        ->toContain('ib-status-phone')
        ->toContain('Accepting shares your phone and WhatsApp with each other.')
        ->and($show)->toContain('Accepting shares your phone and WhatsApp with each other.');
});
