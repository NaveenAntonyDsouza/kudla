<?php

use App\Services\MemberEmailService;
use App\Support\EmailAlerts;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Settings -> Manage Alerts (owner 20 Nov 2026): every email is ticked until the member switches it
| off, the page shows exactly what we send, and saving never undoes an unsubscribe made elsewhere.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

function alertsPage($test, $user)
{
    return $test->actingAs($user)->get('/settings?section=manage_alerts')->assertOk();
}

it('shows every email alert ticked for a member who never changed anything', function () {
    $user = $this->b->m['TP']->user;
    $user->update(['notification_preferences' => null]);

    $html = alertsPage($this, $user)->getContent();
    foreach (array_keys(EmailAlerts::LIST) as $key) {
        expect($html)->toMatch('/data-alert="' . $key . '">\s*<input type="checkbox" name="' . $key . '" value="1"\s*checked/');
    }
});

it('shows an email unticked when the member unsubscribed from it by link, and saving keeps other choices', function () {
    $user = $this->b->m['TP']->user;
    // unsubscribed from weekly matches by email link; push choice made in the app
    $user->update(['notification_preferences' => ['email_weekly_matches' => false, 'push_views' => false, 'quiet_hours_start' => '22:00']]);

    $html = alertsPage($this, $user)->getContent();
    expect($html)->toMatch('/name="email_weekly_matches" value="1"\s*class/')       // not checked
        ->toMatch('/name="email_promotions" value="1"\s*checked/');                // still on, as we send it

    // save the page with only "Interest declined" unticked (and weekly matches left unticked)
    $ticked = array_diff(array_keys(EmailAlerts::LIST), ['email_declined', 'email_weekly_matches']);
    $this->actingAs($user)->post('/settings/alerts', array_fill_keys($ticked, '1'))->assertRedirect();

    $user->refresh();
    expect($user->wantsNotification('email_declined'))->toBeFalse()
        ->and($user->wantsNotification('email_weekly_matches'))->toBeFalse()   // the unsubscribe survived
        ->and($user->wantsNotification('email_interest'))->toBeTrue()
        ->and($user->notification_preferences['push_views'])->toBeFalse()      // app choice kept
        ->and($user->notification_preferences['quiet_hours_start'])->toBe('22:00');
});

it('lets photo emails be switched off on their own, and follows "New interests" until then', function () {
    $user = $this->b->m['TP']->user;

    $user->update(['notification_preferences' => ['email_interest' => false]]);
    expect($user->fresh()->wantsNotification('email_photos'))->toBeFalse();     // old opt-out still covers photos

    $user->update(['notification_preferences' => ['email_interest' => true, 'email_photos' => false]]);
    $user = $user->fresh();
    expect($user->wantsNotification('email_photos'))->toBeFalse()
        ->and($user->wantsNotification('email_interest'))->toBeTrue();
});

it('sends no photo email to a member who switched photo emails off', function () {
    $b = $this->b;
    $asker = $b->m['VS'];
    $owner = $b->m['TP'];
    $owner->user->update(['notification_preferences' => ['email_photos' => false]]);
    Mail::fake();

    $this->actingAs($asker->user)->post('/photo-requests/' . $owner->matri_id . '/send');

    Mail::assertNotQueued(\App\Mail\PhotoRequestReceivedMail::class, fn ($m) => $m->hasTo($owner->user->email));
    Mail::assertNotQueued(\App\Mail\PhotoUploadRequestedMail::class, fn ($m) => $m->hasTo($owner->user->email));
});

it('still sends the photo email when photo emails are on (control for the test above)', function () {
    $b = $this->b;
    $asker = $b->m['VS'];
    $owner = $b->m['TP'];
    $owner->user->update(['notification_preferences' => null]);
    Mail::fake();

    $this->actingAs($asker->user)->post('/photo-requests/' . $owner->matri_id . '/send');

    $sent = Mail::queued(\App\Mail\PhotoRequestReceivedMail::class, fn ($m) => $m->hasTo($owner->user->email))->count()
        + Mail::queued(\App\Mail\PhotoUploadRequestedMail::class, fn ($m) => $m->hasTo($owner->user->email))->count();
    expect($sent)->toBeGreaterThan(0);
});
