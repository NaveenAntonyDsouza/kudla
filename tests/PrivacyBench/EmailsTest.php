<?php

use App\Models\Interest;
use App\Models\PhotoRequest;
use App\Models\User;
use App\Services\InterestService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Every email the site sends that involves another member — interest received /
| accepted / declined / reminder / expired, photo requests, the weekly matches, the
| "who viewed you" digest, re-engagement, "still looking?" — rendered as its
| recipient gets it and scanned for other members' private details and for members
| the recipient has no reason to see.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('sends no member another member\'s private details, or members they have no reason to see, in any email', function () {
    $b = $this->b;

    // more traffic through the real actions: a photo request and its approval, a decline
    $this->actingAs($b->m['VS']->user)->post('/photo-requests/' . $b->m['TP']->matri_id . '/send');
    $request = PhotoRequest::where('requester_profile_id', $b->m['VS']->id)->where('target_profile_id', $b->m['TP']->id)->first();
    if ($request) {
        $this->actingAs($b->m['TP']->user)->post('/photo-requests/' . $request->id . '/approve');
    }
    app(InterestService::class)->decline($b->interests['C_TP']->fresh(), null, 'CNYMSG0209D no thanks');

    // age things so the scheduled emails have work: reminders (3+ days), expiry (30+ days),
    // re-engagement (inactive a week), "still looking?" (long inactive)
    DB::table('interests')->where('id', $b->interests['VS_T']->id)->update(['created_at' => now()->subDays(4)]);
    $old = app(InterestService::class)->send($b->m['VS'], $b->m['TP'], null, 'CNYMSG0702A old note');
    DB::table('interests')->where('id', $old->id)->update(['created_at' => now()->subDays(31)]);
    User::whereKey($b->m['W']->user_id)->update(['last_login_at' => now()->subDays(8)]);
    User::whereKey($b->m['VB']->user_id)->update(['last_login_at' => now()->subDays(200)]);

    foreach ([
        ['engagement:send-interest-reminders', []],
        ['engagement:send-weekly-matches', []],
        ['engagement:send-profile-views-digest', ['--delay' => 0]],
        ['engagement:send-reengagement', []],
        ['interests:expire', []],
        ['members:dormant', ['--delay' => 0]],
    ] as [$command, $args]) {
        Artisan::call($command, $args);
    }

    $mails = Mail::sent(fn (Mailable $m) => true)->merge(Mail::queued(fn (Mailable $m) => true));
    $problems = [];
    $kinds = [];
    foreach ($mails as $mail) {
        $address = $mail->to[0]['address'] ?? null;
        $viewer = User::where('email', $address)->first()?->profile;
        $kind = class_basename($mail);
        $kinds[$kind] = ($kinds[$kind] ?? 0) + 1;
        if (! $viewer) {
            continue; // staff / site mailbox
        }
        $html = urldecode((string) $mail->render());
        $isList = in_array($kind, ['WeeklyMatchSuggestionsMail', 'ProfileViewsDigestMail'], true);
        $linked[$kind] = ($linked[$kind] ?? 0) + preg_match_all('#/profile/[A-Z]{2,4}\d{5,}#', $html);
        array_push($problems, ...$b->scan($html, $viewer, "{$kind} to " . $b->keyOf($viewer->id)),
            ...$b->listingViolations($html, $viewer, "{$kind} to " . $b->keyOf($viewer->id), $isList));
    }
    ksort($kinds);
    fwrite(STDERR, "\n  emails checked: " . json_encode($kinds) . "\n");

    // the emails this test is meant to reach were really sent
    expect(array_keys($kinds))->toContain('InterestReceivedMail', 'InterestAcceptedMail', 'WeeklyMatchSuggestionsMail', 'ProfileViewsDigestMail')
        // the member lists in these emails link to profiles, so the strict check had members to check
        ->and($linked['WeeklyMatchSuggestionsMail'] ?? 0)->toBeGreaterThan(0)
        ->and($linked['ProfileViewsDigestMail'] ?? 0)->toBeGreaterThan(0)
        ->and(array_values(array_unique($problems)))->toBe([]);
});
