<?php

use App\Models\BlockedProfile;
use App\Services\InterestService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The notifications page names the other member ("Priya D.", owner 20 Nov 2026) only while the viewer
| could open that member's profile; the notification text itself keeps the Matri ID (it is also push).
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('shows the sender\'s short name on the notifications page, and hides it once either side blocks', function () {
    $b = $this->b;
    $sender = $b->m['VS'];
    $viewer = $b->m['TP'];
    $sender->update(['full_name' => 'Notifina Familyname']);
    app(InterestService::class)->send($sender, $viewer, null, null);

    $page = $this->actingAs($viewer->user)->get('/notifications')->assertOk();
    $page->assertSee('Notifina F.')->assertDontSee('Familyname');

    BlockedProfile::create(['profile_id' => $sender->id, 'blocked_profile_id' => $viewer->id]);
    $this->actingAs($viewer->user)->get('/notifications')->assertOk()->assertDontSee('Notifina');
});
