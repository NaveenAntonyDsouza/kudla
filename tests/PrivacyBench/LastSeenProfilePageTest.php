<?php

use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use App\Support\AdminModules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The "Last Login" line on another member's full profile (16 Nov 2026). With no sign-in on
| record it showed the date the profile was last EDITED, so a member staff updated on a
| call today read as "Last Login: today". Under the "Last seen" switch it uses the same
| wording as the cards; off, it is exactly the old line.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue(AdminModules::LAST_SEEN, '0');
});

afterEach(fn () => SiteSetting::setValue(AdminModules::LAST_SEEN, '0'));

function seenMember(string $tag, string $phone, string $gender): \App\Models\Profile
{
    $profile = app(MemberCreationService::class)->create([
        'full_name' => 'Seen '.$tag, 'gender' => $gender, 'date_of_birth' => '1992-05-05',
        'email' => 'seen-'.$tag.'@bench.test', 'phone' => $phone,
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'seen-pass-9'])['profile'];
    $profile->forceFill(['onboarding_completed' => true])->save();

    return $profile->fresh();
}

it('stops showing an edit date as a login on someone else\'s profile', function () {
    $viewer = seenMember('viewer', '9812700001', 'male');
    $absent = seenMember('absent', '9812700002', 'female');

    // joined in June, no sign-in on record, details edited by staff today
    DB::table('users')->where('id', $absent->user_id)->update([
        'last_login_at' => null, 'created_at' => '2026-06-10 10:00:00', 'updated_at' => now(),
    ]);

    $page = fn () => (string) test()->actingAs($viewer->user)->get('/profile/'.$absent->matri_id)->assertOk()->getContent();

    // off: the old line, today's edit date passed off as a login
    expect($page())->toContain('Last Login : '.now()->format('d M Y'));

    SiteSetting::setValue(AdminModules::LAST_SEEN, '1');
    expect($page())->toContain('Last seen over')->not->toContain('Last Login :');

    // a real sign-in reads as such
    DB::table('users')->where('id', $absent->user_id)->update(['last_login_at' => now()->subDays(12)]);
    expect($page())->toContain('Last seen 12 days ago');
});
