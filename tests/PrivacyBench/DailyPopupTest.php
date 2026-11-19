<?php

use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use App\Support\AdminModules;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| One pop-up a day at most (19 Nov 2026): photo first, then "waiting for your
| reply", then plans for free members (every 4 days), then the WhatsApp Channel
| (once ever). The three new ones have their own switches, off by default.
*/

const DP_SWITCHES = [AdminModules::PHOTO_POPUP, AdminModules::WAITING_POPUP, AdminModules::PLANS_POPUP, AdminModules::CHANNEL_POPUP];

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    foreach (DP_SWITCHES as $key) {
        SiteSetting::setValue($key, '0');
    }
    SiteSetting::setValue('free_membership_enabled', '0');
    SiteSetting::setValue('whatsapp_channel_url', 'https://whatsapp.com/channel/0029BenchChannel00000000');
    if (! MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)->exists()) {
        MembershipPlan::create(['plan_name' => 'Bench Gold', 'slug' => 'bench-gold', 'duration_months' => 6, 'price_inr' => 1500, 'is_active' => true, 'sort_order' => 9]);
    }
    Cache::flush();
});

afterEach(function () {
    foreach (DP_SWITCHES as $key) {
        SiteSetting::setValue($key, '0');
    }
    $this->travelBack();
});

/** A finished member who joined $daysAgo days ago, with or without a photo. */
function popupMember(string $tag, string $phone, bool $photo = true, int $daysAgo = 10): \App\Models\Profile
{
    $profile = app(MemberCreationService::class)->create([
        'full_name' => 'Popup '.$tag, 'gender' => 'female', 'date_of_birth' => '1994-02-02',
        'email' => 'popup-'.$tag.'@bench.test', 'phone' => $phone,
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'popup-pass-9'])['profile'];
    $profile->forceFill(['onboarding_completed' => true])->save();
    $profile->user->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
    if ($photo) {
        DB::table('profile_photos')->insert(['profile_id' => $profile->id, 'photo_type' => 'profile', 'photo_url' => 'photos/bench-popup.jpg',
            'is_primary' => true, 'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
    }

    return $profile->fresh();
}

function popupOn(\App\Models\Profile $p, string $url = '/dashboard'): ?string
{
    $html = (string) test()->actingAs($p->user)->get($url)->assertOk()->getContent();

    return preg_match('/data-daily-popup="([a-z]+)"/', $html, $m) ? $m[1] : null;
}

it('shows nothing new while the new switches are off', function () {
    $p = popupMember('off', '9812700001');

    expect(popupOn($p))->toBeNull();
});

it('asks for a reply when an interest has waited 3 days, once a day, never on the interests page', function () {
    SiteSetting::setValue(AdminModules::WAITING_POPUP, '1');
    $her = popupMember('wait', '9812700002');
    $him = popupMember('sender', '9812700003');
    $i = Interest::create(['sender_profile_id' => $him->id, 'receiver_profile_id' => $her->id, 'status' => 'pending']);

    DB::table('interests')->where('id', $i->id)->update(['created_at' => now()->subDays(2)]);
    expect(popupOn($her))->toBeNull(); // only 2 days: no pop-up, and the day is not used up

    DB::table('interests')->where('id', $i->id)->update(['created_at' => now()->subDays(4)]);
    expect(popupOn($her, '/interests?tab=received'))->toBeNull();

    $html = (string) test()->actingAs($her->user)->get('/dashboard')->getContent();
    expect($html)->toContain('data-daily-popup="waiting"')->toContain('1 member is waiting for your reply')
        ->and(popupOn($her))->toBeNull();
});

it('puts the photo pop-up first, and shows only one a day', function () {
    SiteSetting::setValue(AdminModules::PHOTO_POPUP, '1');
    SiteSetting::setValue(AdminModules::WAITING_POPUP, '1');
    $her = popupMember('nophoto', '9812700004', photo: false);
    $him = popupMember('sender2', '9812700005');
    $i = Interest::create(['sender_profile_id' => $him->id, 'receiver_profile_id' => $her->id, 'status' => 'pending']);
    DB::table('interests')->where('id', $i->id)->update(['created_at' => now()->subDays(5)]);

    expect(popupOn($her))->toBe('photo')
        ->and(popupOn($her))->toBeNull();
});

it('shows free members the plans at most every 4 days, never to new or paying members', function () {
    SiteSetting::setValue(AdminModules::PLANS_POPUP, '1');
    $new = popupMember('newbie', '9812700006', daysAgo: 1);
    expect(popupOn($new))->toBeNull();

    $free = popupMember('free', '9812700007');
    $html = (string) test()->actingAs($free->user)->get('/dashboard')->getContent();
    $cheapest = MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)->orderBy('price_inr')->first();
    expect($html)->toContain('data-daily-popup="plans"')->toContain(e($cheapest->plan_name))
        ->toContain('₹'.number_format($cheapest->price_inr))->toContain('from=popup')
        ->toContain('chat with members')
        ->toContain('data-popup-close')
        // each plan line opens that plan's card on the plans page
        ->toContain('#plan-'.$cheapest->id);
    if ($cheapest->duration_months > 1) {
        expect($html)->toContain('₹'.number_format(round($cheapest->price_inr / $cheapest->duration_months)).' a month');
    }

    $this->travel(1)->days();
    expect(popupOn($free))->toBeNull();
    $this->travel(3)->days();
    expect(popupOn($free))->toBe('plans');

    $paid = popupMember('paid', '9812700008');
    DB::table('user_memberships')->insert(['user_id' => $paid->user_id, 'plan_id' => $cheapest->id, 'is_active' => true,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now()]);
    expect(popupOn($paid))->toBeNull();
});

it('shows the WhatsApp Channel once per member, ever', function () {
    SiteSetting::setValue(AdminModules::CHANNEL_POPUP, '1');
    $p = popupMember('channel', '9812700009');

    $html = (string) test()->actingAs($p->user)->get('/dashboard')->getContent();
    expect($html)->toContain('data-daily-popup="channel"')->toContain('https://whatsapp.com/channel/0029BenchChannel00000000');

    $this->travel(2)->days();
    expect(popupOn($p))->toBeNull();
});

it('shows none while staff look through Login as member', function () {
    SiteSetting::setValue(AdminModules::CHANNEL_POPUP, '1');
    $p = popupMember('loginas', '9812700010');

    request()->attributes->set('login_as.active', true);
    expect(\App\Support\DailyPopup::choose($p->user))->toBeNull();
    request()->attributes->set('login_as.active', false);
    expect(\App\Support\DailyPopup::choose($p->user))->toBe('channel');
});
