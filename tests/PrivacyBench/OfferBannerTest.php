<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The launch-offer banner (owner, 16 Nov 2026): the coupon code is bold where the text names
| it, and the "View Plans" button is left off the plans page it would only reload.
*/

const OFFER_KEYS = ['free_membership_enabled', 'offer_banner_enabled', 'offer_banner_title', 'offer_banner_text', 'offer_banner_coupon_code', 'offer_banner_membership_only'];

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->offerBefore = collect(OFFER_KEYS)->mapWithKeys(fn ($k) => [$k => SiteSetting::getValue($k, '')])->all();
    SiteSetting::setValue('free_membership_enabled', '0');
    SiteSetting::setValue('offer_banner_enabled', '1');
    SiteSetting::setValue('offer_banner_title', 'Launch offer');
    SiteSetting::setValue('offer_banner_coupon_code', 'KUDLA500');
    SiteSetting::setValue('offer_banner_membership_only', '0');
});

afterEach(function () {
    foreach ($this->offerBefore as $k => $v) {
        SiteSetting::setValue($k, (string) $v);
    }
});

it('puts the code in bold inside the text, and keeps typed text from becoming markup', function () {
    SiteSetting::setValue('offer_banner_text', 'Use the code KUDLA500 for <b>₹500</b> off.');

    $html = (string) $this->get('/membership-plans')->assertOk()->getContent();

    expect($html)->toContain('Use the code <strong style="font-weight:800;color:#fff;letter-spacing:.02em">KUDLA500</strong> for &lt;b&gt;₹500&lt;/b&gt; off.')
        ->not->toContain('<b>₹500</b>');
});

it('leaves the View Plans button off the plans page, and keeps it everywhere else', function () {
    SiteSetting::setValue('offer_banner_text', 'Use the code KUDLA500 for a further ₹500 off.');

    expect((string) $this->get('/membership-plans')->assertOk()->getContent())->not->toContain('View Plans')
        ->and((string) $this->get('/')->assertOk()->getContent())->toContain('View Plans');
});
