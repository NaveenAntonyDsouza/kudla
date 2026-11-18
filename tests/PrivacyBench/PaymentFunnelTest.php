<?php

use App\Filament\Pages\PaymentFunnel as PaymentFunnelPage;
use App\Models\Coupon;
use App\Models\MemberEvent;
use App\Models\MembershipPlan;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InterestService;
use App\Services\MemberCreationService;
use App\Services\Payment\SubscriptionActivator;
use App\Support\AdminModules;
use App\Support\MemberEvents;
use App\Support\PaymentFunnel;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Payment funnel (owner, 18 Nov 2026: "are you collecting enough metrics regarding
| payment?"). The site kept each payment attempt, but not the plans page, coupon codes
| typed, a closed payment window, the gateway's reason for a failure, or which paid
| feature made a free member want to pay. Now recorded (member_events) and shown in
| Money → Payment Funnel, with the members worth a call.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Cache::flush();
    $this->freeBefore = SiteSetting::getValue('free_membership_enabled', '0');
    SiteSetting::setValue('free_membership_enabled', '0');
    SiteSetting::setValue(AdminModules::PAYMENT_INSIGHTS, '1');
    $this->plan = MembershipPlan::where('is_active', true)->where('price_inr', '>', 0)->orderBy('price_inr')->first()
        ?? MembershipPlan::create(['plan_name' => 'Bench Silver', 'slug' => 'bench-silver', 'duration_months' => 3, 'price_inr' => 1000, 'is_active' => true, 'sort_order' => 90]);
});

afterEach(function () {
    SiteSetting::setValue('free_membership_enabled', (string) $this->freeBefore);
    SiteSetting::setValue(AdminModules::PAYMENT_INSIGHTS, '1');
});

function payMember(string $tag, string $phone, string $gender = 'male'): \App\Models\Profile
{
    $profile = app(MemberCreationService::class)->create([
        'full_name' => 'Pay '.$tag, 'gender' => $gender, 'date_of_birth' => $gender === 'male' ? '1991-02-02' : '1995-02-02',
        'email' => 'pay-'.$tag.'@bench.test', 'phone' => $phone,
        'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ], ['temp_password' => 'pay-pass-9'])['profile'];
    $profile->forceFill(['onboarding_completed' => true])->save();

    return $profile->fresh();
}

function events(string $event, ?int $userId = null)
{
    return MemberEvent::where('event', $event)->when($userId, fn ($q) => $q->where('user_id', $userId));
}

it('records the plans page once a day, members and visitors apart, and nothing in free mode', function () {
    $m = payMember('plans', '9812900001');

    $this->actingAs($m->user)->get('/membership-plans')->assertOk();
    $this->actingAs($m->user)->get('/membership-plans')->assertOk();
    expect(events(MemberEvents::PLANS_VIEWED, $m->user_id)->count())->toBe(1)
        ->and(events(MemberEvents::PLANS_VIEWED, $m->user_id)->value('subject'))->toBe('member');

    auth()->logout();
    $this->get('/membership-plans')->assertOk();
    expect(events(MemberEvents::PLANS_VIEWED)->whereNull('user_id')->where('subject', 'visitor')->count())->toBe(1);

    SiteSetting::setValue('free_membership_enabled', '1');
    Cache::flush();
    $this->actingAs($m->user)->get('/membership-plans')->assertOk();
    expect(events(MemberEvents::PLANS_VIEWED, $m->user_id)->count())->toBe(1);
});

it('records every coupon code typed and whether it worked', function () {
    $m = payMember('coupon', '9812900002');
    Coupon::create(['code' => 'BENCH500', 'discount_type' => 'fixed', 'discount_value' => 50000, 'is_active' => true,
        'usage_limit_per_user' => 1, 'valid_until' => now()->addMonth()->toDateString()]);

    $this->actingAs($m->user)->postJson('/membership-plans/apply-coupon', ['coupon_code' => 'kudla 500', 'plan_id' => $this->plan->id])->assertJson(['valid' => false]);
    $this->actingAs($m->user)->postJson('/membership-plans/apply-coupon', ['coupon_code' => 'bench500', 'plan_id' => $this->plan->id])->assertJson(['valid' => true]);

    $tries = events(MemberEvents::COUPON_TRIED, $m->user_id)->orderBy('id')->get();
    expect($tries)->toHaveCount(2)
        ->and($tries[0]->subject)->toBe('KUDLA 500')->and($tries[0]->data['valid'])->toBeFalse()
        ->and($tries[1]->subject)->toBe('BENCH500')->and($tries[1]->data['valid'])->toBeTrue();
});

it('keeps why a payment failed, from the payment window and from the gateway, without the window deciding it', function () {
    $m = payMember('fail', '9812900003');
    $other = payMember('other', '9812900004');
    $sub = Subscription::create(['user_id' => $m->user_id, 'plan_id' => (string) $this->plan->id, 'plan_name' => $this->plan->plan_name,
        'gateway' => 'razorpay', 'amount' => 100000, 'original_amount' => 100000, 'discount_amount' => 0, 'payment_status' => 'pending']);

    // another member cannot write on this payment
    $this->actingAs($other->user)->post('/membership-plans/event', ['event' => 'payment_failed', 's' => $sub->id, 'reason' => 'x'])->assertNoContent();
    expect($sub->fresh()->gateway_metadata['failure_reason'] ?? null)->toBeNull();

    $this->actingAs($m->user)->post('/membership-plans/event', ['event' => 'payment_failed', 's' => $sub->id, 'code' => 'BAD_REQUEST_ERROR', 'reason' => 'Payment declined by the bank', 'step' => 'payment_authorization'])->assertNoContent();
    $this->actingAs($m->user)->post('/membership-plans/event', ['event' => 'checkout_closed', 's' => $sub->id])->assertNoContent();

    $sub->refresh();
    expect($sub->payment_status)->toBe('pending')                 // only the gateway marks it failed
        ->and($sub->gateway_metadata['failure_reason'])->toContain('declined by the bank')
        ->and(events(MemberEvents::CHECKOUT_CLOSED, $m->user_id)->count())->toBe(1);

    app(SubscriptionActivator::class)->markFailed($sub, 'UPI request declined — U30');
    $sub->refresh();
    expect($sub->payment_status)->toBe('failed')
        ->and($sub->gateway_metadata['failure_reason'])->toBe('UPI request declined — U30')
        ->and(events(MemberEvents::PAYMENT_FAILED, $m->user_id)->where('data->from', 'gateway')->count())->toBe(1);

    $reasons = (new PaymentFunnel(7))->failureReasons();
    expect(array_keys($reasons))->toContain('Razorpay: UPI request declined — U30');
});

it('counts a free member stopped by a paid feature, once a day', function () {
    $man = payMember('wall', '9812900005');
    $woman = payMember('wallw', '9812900006', 'female');

    foreach ([1, 2] as $try) {
        try {
            app(InterestService::class)->send($man, $woman, null, 'Hello, I liked your profile');
        } catch (\RuntimeException $e) {
            expect($e->getMessage())->toContain('Upgrade to a paid plan');
        }
    }

    expect(events(MemberEvents::PAYWALL, $man->user_id)->where('subject', 'personal_message')->count())->toBe(1);
    expect((new PaymentFunnel(7))->paywall()['personal_message'])->toMatchArray(['times' => 1, 'members' => 1, 'paid' => 0]);
});

it('lists unfinished payments to call, and the Work Queue counts the same ones', function () {
    $left = payMember('left', '9812900007');
    $paid = payMember('paid', '9812900008');
    $mk = fn ($p, $status, $ago) => tap(Subscription::create(['user_id' => $p->user_id, 'plan_id' => (string) $this->plan->id, 'plan_name' => $this->plan->plan_name,
        'gateway' => 'phonepe', 'amount' => 100000, 'original_amount' => 100000, 'discount_amount' => 0, 'payment_status' => $status]),
        fn ($s) => DB::table('subscriptions')->where('id', $s->id)->update(['created_at' => now()->subMinutes($ago), 'updated_at' => now()->subMinutes($ago)]));

    $mk($left, 'pending', 120);
    $mk($paid, 'pending', 180);
    $mk($paid, 'paid', 150);       // paid after leaving the first one: not to call

    $call = collect((new PaymentFunnel(7))->toCall()['unfinished'])->keyBy(fn ($r) => $r['profile']->matri_id);
    expect($call->keys())->toContain($left->matri_id)->not->toContain($paid->matri_id)
        ->and($call[$left->matri_id]['gateway'])->toBe('PhonePe')
        ->and($call[$left->matri_id]['called'])->toBeNull();

    // a Call note after the last try marks them as called
    \App\Models\ProfileNote::create(['profile_id' => $left->id, 'admin_user_id' => $paid->user_id, 'note' => 'Called about the payment', 'note_type' => 'call']);
    expect(collect((new PaymentFunnel(7))->toCall()['unfinished'])->firstWhere(fn ($r) => $r['profile']->id === $left->id)['called'])->not->toBeNull();

    $queue = PaymentFunnel::unfinishedQuery()->whereIn('user_id', [$left->user_id, $paid->user_id])->pluck('user_id')->all();
    expect($queue)->toBe([$left->user_id]);
});

it('never records staff, shows the page to the owner, and hides it when switched off', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $owner = new User;
    $owner->forceFill(['name' => 'Pay Owner', 'email' => 'pay-owner@bench.test', 'password' => bcrypt('x-pay-owner-5'), 'role' => 'admin',
        'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now(), 'phone' => '9845044401'])->save();

    MemberEvents::record(MemberEvents::PLANS_VIEWED, 'member', [], $owner);
    expect(events(MemberEvents::PLANS_VIEWED, $owner->id)->count())->toBe(0);

    app('auth')->forgetGuards();
    $this->actingAs($owner, 'admin');
    app('auth')->shouldUse('admin');
    expect(PaymentFunnelPage::canAccess())->toBeTrue();
    Livewire::test(PaymentFunnelPage::class)->assertSee('Members at each step')->assertSee('worth a call')->set('days', 30)->assertSee('Last 30 days');

    SiteSetting::setValue(AdminModules::PAYMENT_INSIGHTS, '0');
    expect(PaymentFunnelPage::canAccess())->toBeFalse();
    MemberEvents::record(MemberEvents::PLANS_VIEWED, 'visitor');
    expect(events(MemberEvents::PLANS_VIEWED)->where('created_at', '>=', now()->subMinute())->whereNull('user_id')->count())->toBe(0);
});
