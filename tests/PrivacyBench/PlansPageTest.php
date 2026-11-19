<?php

use App\Models\MembershipPlan;
use App\Models\SiteSetting;
use Tests\PrivacyBench\Bench;

/*
| Plans page rows (owner, 19 Nov 2026, after comparing with Chavara's Compare Plans):
| every row follows the rule the site applies; "Featured Profile", "Priority Support" and
| "Taxes extra as applicable" were promises nothing kept; chat and "free members can reply
| to you" were real but missing; what every member gets is listed too.
*/

beforeEach(function () {
    Bench::guard();
    $this->freeBefore = SiteSetting::getValue('free_membership_enabled', '0');
    $this->weeklyBefore = SiteSetting::getValue('weekly_matches_enabled', '1');
    SiteSetting::setValue('free_membership_enabled', '0');
    SiteSetting::setValue('weekly_matches_enabled', '1');
});

afterEach(function () {
    SiteSetting::setValue('free_membership_enabled', (string) $this->freeBefore);
    SiteSetting::setValue('weekly_matches_enabled', (string) $this->weeklyBefore);
});

it('shows only rows the site keeps, the monthly price, and what everyone gets', function () {
    $page = $this->get('/membership-plans')->assertOk();

    $page->assertSee('Phone &amp; WhatsApp numbers', false)
        ->assertSee('Send chat messages')
        ->assertSee('See who viewed your profile')
        ->assertSee('Shown higher in search')
        ->assertSee('Create profile, search, send &amp; receive interests', false)
        ->assertSee('Weekly match suggestions by email')
        ->assertDontSee('Featured Profile')
        ->assertDontSee('Priority Support')
        ->assertDontSee('Taxes extra');

    $plan = MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)->where('duration_months', '>', 1)->orderBy('price_inr')->first();
    if ($plan) {
        $page->assertSee('&#8377;'.number_format(round($plan->price_inr / $plan->duration_months)).' a month', false);
    }

    // "Free members can reply to you" only when some active plan really allows it
    $anyAllows = MembershipPlan::where('is_active', true)->where('allows_free_member_chat', true)->exists();
    $anyAllows ? $page->assertSee('Free members can reply to you') : $page->assertDontSee('Free members can reply to you');
});

it('does not promise the weekly email on a site that switched it off', function () {
    SiteSetting::setValue('weekly_matches_enabled', '0');

    $this->get('/membership-plans')->assertOk()->assertDontSee('Weekly match suggestions by email');
});
