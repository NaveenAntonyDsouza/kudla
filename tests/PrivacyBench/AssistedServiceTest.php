<?php

use App\Filament\Pages\AssistedMembers;
use App\Filament\Pages\StaffActivity as StaffActivityPage;
use App\Models\AdminActivityLog;
use App\Models\AdminRecommendation;
use App\Models\AssistedEvent;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserMembership;
use App\Support\Assisted;
use App\Support\StaffActivity;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Assisted service (12 Nov 2026, the portal way). On the bench, T (female) buys an
| assisted plan; her relationship manager is a telecaller account. Promised:
|   - staff work only for a member on an assisted plan, with her own consent ticked,
|     and only the named manager (or the owner); withdrawing consent stops it;
|   - a suggestion must be a profile T may see (never same gender, blocked, hidden
|     or limited by its privacy settings), and T says yes or no; yes sends the
|     interest in her name, marked as via the manager;
|   - "may send without asking" sends straight away;
|   - staff answer an interest T received only with her instruction written down,
|     and T sees it;
|   - no other member can answer T's suggestions or see her Assisted page;
|   - a manager who can't see everyone sees only their own members.
*/

beforeEach(function () {
    Bench::guard();
    Http::fake();
    Mail::fake();
    $this->bench = Bench::build();
    foreach ($this->bench->m as $p) {
        $p->forceFill(['onboarding_completed' => true])->save();
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $role = fn (string $slug) => DB::table('staff_roles')->where('slug', $slug)->value('id');
    $staff = fn (string $name, string $slug, string $phone) => User::create(['name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test',
        'password' => bcrypt('x-assist-pass-3'), 'role' => 'admin', 'is_active' => true, 'staff_role_id' => $role($slug), 'email_verified_at' => now(), 'phone' => $phone])->fresh();
    $this->owner = $staff('Assist Owner', 'super_admin', '9845011101');
    $this->rm = $staff('Rita Manager', 'telecaller', '9845011102');      // can't see every assisted member
    $this->otherRm = $staff('Other Manager', 'telecaller', '9845011103');

    $this->plan = MembershipPlan::create(['plan_name' => 'Bench Assisted', 'slug' => 'bench-assisted', 'duration_months' => 3, 'price_inr' => 24900,
        'daily_interest_limit' => 20, 'can_view_contact' => true, 'is_active' => true, 'is_assisted' => true]);
    $this->t = $this->bench->m['T'];
    UserMembership::create(['user_id' => $this->t->user_id, 'plan_id' => $this->plan->id, 'starts_at' => now(), 'ends_at' => now()->addMonths(3), 'is_active' => true]);

    $this->asStaff = function (User $u) {
        app('auth')->forgetGuards();
        $this->actingAs($u, 'admin');
        app('auth')->shouldUse('admin');
    };
    $this->asMember = function (Profile $p) {
        app('auth')->forgetGuards();
        $this->actingAs($p->user->fresh(), 'web');
        app('auth')->shouldUse('web');
    };
    $this->ready = function () {   // manager set + T's own consent, through her page
        ($this->asStaff)($this->owner);
        Assisted::setManager($this->t, $this->rm);
        ($this->asMember)($this->t);
        $this->post('/assisted-service/consent', ['agree' => '1'])->assertRedirect();
        $this->t->refresh();
        ($this->asStaff)($this->rm);
    };
});

it('lets staff work only on an assisted plan, with a manager, the member\'s consent, and only the named manager', function () {
    $t = $this->t;
    $v = $this->bench->m['V'];

    expect(Assisted::refusal($this->rm, $t))->toContain('relationship manager');
    Assisted::setManager($t, $this->rm);
    expect(Assisted::refusal($this->rm, $t->refresh()))->toContain('not given consent')
        ->and(fn () => Assisted::suggest($this->rm, $t, $v))->toThrow(DomainException::class, 'not given consent');

    ($this->asMember)($t);
    $this->post('/assisted-service/consent', [])->assertSessionHasErrors('agree');   // the tick is required
    $this->post('/assisted-service/consent', ['agree' => '1'])->assertRedirect();
    $t->refresh();
    expect($t->assisted_consent_at)->not->toBeNull()->and($t->assisted_auto_send)->toBeFalse()
        ->and(Assisted::refusal($this->rm, $t))->toBeNull()
        ->and(Assisted::refusal($this->otherRm, $t))->toContain('Only the member\'s relationship manager (Rita Manager)')
        ->and(Assisted::refusal($this->owner, $t))->toBeNull()              // the owner can cover
        ->and(Assisted::refusal($this->bench->staff, $this->bench->m['W']))->toContain('not on an assisted plan');

    // the plan ends: work stops
    UserMembership::where('user_id', $t->user_id)->update(['is_active' => false]);
    expect(Assisted::refusal($this->rm, $t->refresh()))->toContain('not on an assisted plan');

    // a member's own forms can never set these
    $t->fill(['relationship_manager_id' => $this->otherRm->id, 'assisted_consent_at' => null, 'assisted_auto_send' => true]);
    expect($t->isDirty())->toBeFalse();
});

it('suggests only profiles the member may see; the member says yes or no; yes sends the interest as via the manager', function () {
    ($this->ready)();
    $t = $this->t;
    $m = $this->bench->m;

    // never: herself, same gender, blocked, already an interest, limited by privacy settings
    foreach ([[$t, 'member themselves'], [$m['W'], 'same gender'], [$m['VB'], 'cannot see'], [$m['VS'], 'already have an interest'], [$m['C'], 'already have an interest']] as [$o, $why]) {
        expect(fn () => Assisted::suggest($this->rm, $t, $o))->toThrow(DomainException::class, $why);
    }
    $m['V']->forceFill(['is_hidden' => true])->save();
    expect(fn () => Assisted::suggest($this->rm, $t, $m['V']->fresh()))->toThrow(DomainException::class, 'cannot see');
    $m['V']->forceFill(['is_hidden' => false])->save();

    $rec = Assisted::suggest($this->rm, $t, $m['V']->fresh(), 'Same parish, works in Dubai');
    expect(fn () => Assisted::suggest($this->rm, $t, $m['V']->fresh()))->toThrow(DomainException::class, 'Already suggested')
        ->and(DB::table('notifications')->where('user_id', $t->user_id)->where('type', 'assisted_suggestion')->count())->toBe(1)
        ->and(Interest::where('sender_profile_id', $t->id)->where('receiver_profile_id', $m['V']->id)->exists())->toBeFalse(); // nothing sent before she answers

    // her page lists it with the manager's note; she says yes
    ($this->asMember)($t);
    $this->get('/assisted-service')->assertOk()->assertSee($m['V']->matri_id)->assertSee('Same parish, works in Dubai')->assertSee('Rita Manager');
    $this->post('/assisted-service/suggestions/'.$rec->id, ['answer' => 'yes'])->assertRedirect()->assertSessionHas('success', fn ($s) => str_contains($s, 'Interest sent'));

    $interest = Interest::where('sender_profile_id', $t->id)->where('receiver_profile_id', $m['V']->id)->first();
    expect($interest)->not->toBeNull()
        ->and((int) $interest->sent_by_admin_id)->toBe($this->rm->id)
        ->and($interest->source)->toBe('assisted')
        ->and($rec->fresh()->interest_id)->toBe($interest->id)
        ->and($rec->fresh()->stage())->toBe('sent');
    $this->post('/assisted-service/suggestions/'.$rec->id, ['answer' => 'no'])->assertSessionHas('error');   // answered once

    // no: nothing is sent
    ($this->asStaff)($this->rm);
    $m['VA']->forceFill(['is_hidden' => false])->save();
    DB::table('interests')->where('id', $this->bench->interests['VA_T']->id)->update(['status' => 'cancelled']);
    $recNo = Assisted::suggest($this->rm, $t, $m['VA']->fresh());
    ($this->asMember)($t);
    $this->post('/assisted-service/suggestions/'.$recNo->id, ['answer' => 'no'])->assertSessionHas('success');
    expect($recNo->fresh()->stage())->toBe('member_no')
        ->and(Interest::where('sender_profile_id', $t->id)->where('receiver_profile_id', $m['VA']->id)->exists())->toBeFalse();
});

it('sends straight away when the member lets the manager send without asking, and stops when she withdraws', function () {
    ($this->asStaff)($this->owner);
    Assisted::setManager($this->t, $this->rm);
    ($this->asMember)($this->t);
    $this->post('/assisted-service/consent', ['agree' => '1', 'auto_send' => '1']);
    ($this->asStaff)($this->rm);
    $t = $this->t->refresh();

    $rec = Assisted::suggest($this->rm, $t, $this->bench->m['V']->fresh());
    expect($rec->fresh()->member_decision)->toBe('auto')
        ->and(Interest::where('sender_profile_id', $t->id)->where('receiver_profile_id', $this->bench->m['V']->id)->value('sent_by_admin_id'))->toBe($this->rm->id)
        ->and(AdminActivityLog::where('admin_user_id', $this->rm->id)->whereIn('action', ['assisted_suggested', 'assisted_interest_sent'])->count())->toBe(2);

    ($this->asMember)($t);
    $this->post('/assisted-service/withdraw')->assertRedirect();
    $t->refresh();
    expect($t->assisted_consent_at)->toBeNull()->and($t->assisted_auto_send)->toBeFalse()
        ->and(Assisted::refusal($this->rm, $t))->toContain('not given consent')
        ->and(AssistedEvent::where('profile_id', $t->id)->pluck('action')->all())->toContain('consent_given', 'consent_withdrawn');
});

it('answers a received interest only with the member\'s instruction, and shows it to her', function () {
    ($this->ready)();
    $t = $this->t;
    $pending = $this->bench->interests['VS_T']->fresh();

    expect(fn () => Assisted::reply($this->rm, $t, $pending, true, '', 'yes'))->toThrow(DomainException::class, 'how the member told you')
        ->and(fn () => Assisted::reply($this->rm, $t, $pending, true, 'phone', ''))->toThrow(DomainException::class, 'what the member said')
        // an interest between two other members
        ->and(fn () => Assisted::reply($this->rm, $t, $this->bench->interests['C_TP']->fresh(), true, 'phone', 'yes please'))->toThrow(DomainException::class, 'not sent to this member')
        ->and(fn () => Assisted::reply($this->otherRm, $t, $pending, true, 'phone', 'yes please'))->toThrow(DomainException::class, 'Only the member');

    $reply = Assisted::reply($this->rm, $t, $pending, true, 'phone', 'She said yes, likes his profile');
    expect($pending->fresh()->status)->toBe('accepted')
        ->and($reply->fresh()->replied_by_admin_id)->toBe($this->rm->id)
        ->and($reply->fresh()->answered_via)->toBe('manager')
        ->and($reply->fresh()->member_instruction)->toBe('Phone call: She said yes, likes his profile')
        ->and(AdminActivityLog::where('action', 'assisted_accepted')->where('model_id', $t->id)->count())->toBe(1);

    ($this->asMember)($t);
    $this->get('/assisted-service')->assertOk()
        ->assertSee('Interests your manager answered for you')
        ->assertSee('Phone call: She said yes, likes his profile')
        ->assertSee($this->bench->m['VS']->matri_id);
});

it('never lets another member answer or see the member\'s suggestions', function () {
    ($this->ready)();
    $rec = Assisted::suggest($this->rm, $this->t, $this->bench->m['V']->fresh(), 'CANARY-NOTE-FOR-T');

    // W, a member without the service, is sent to the plans page; she can't answer T's suggestion
    ($this->asMember)($this->bench->m['W']);
    $this->get('/assisted-service')->assertRedirect(route('membership.index'));
    $this->post('/assisted-service/suggestions/'.$rec->id, ['answer' => 'yes'])->assertNotFound();
    $this->post('/assisted-service/consent', ['agree' => '1'])->assertSessionHas('error');
    expect($rec->fresh()->member_decision)->toBeNull()
        ->and($this->bench->m['W']->fresh()->assisted_consent_at)->toBeNull();

    // V (the match) gets nothing from T's page either
    ($this->asMember)($this->bench->m['V']);
    $this->get('/assisted-service')->assertRedirect()->assertDontSee('CANARY-NOTE-FOR-T');

    // a profile that blocks T after the suggestion is no longer named on her page
    \App\Models\BlockedProfile::create(['profile_id' => $this->bench->m['V']->id, 'blocked_profile_id' => $this->t->id]);
    ($this->asMember)($this->t);
    $this->get('/assisted-service')->assertOk()->assertDontSee($this->bench->m['V']->matri_id)->assertSee('Profile no longer available');
});

it('asks for the manager when an assisted plan is assigned on Change Plan', function () {
    $w = $this->bench->m['W'];
    ($this->asStaff)($this->owner);
    $page = fn () => Livewire::test(\App\Filament\Pages\ChangeMembershipPlan::class)
        ->set('search', $w->matri_id)->call('lookupUser')->call('startAssign')
        ->set('plan_id', (string) $this->plan->id)->set('payment_method', 'gpay')->set('reason', 'Paid at office');

    $page()->call('assignPlan');                     // no manager chosen: refused, nothing assigned
    expect(UserMembership::where('user_id', $w->user_id)->where('plan_id', $this->plan->id)->exists())->toBeFalse();

    $page()->set('manager_id', (string) $this->rm->id)->call('assignPlan');
    $w->refresh();
    expect(UserMembership::where('user_id', $w->user_id)->where('plan_id', $this->plan->id)->where('is_active', true)->exists())->toBeTrue()
        ->and($w->relationship_manager_id)->toBe($this->rm->id)
        ->and($w->assisted_consent_at)->toBeNull()                      // consent is still hers to give
        ->and(AssistedEvent::where('profile_id', $w->id)->where('action', 'manager_set')->exists())->toBeTrue()
        ->and(Assisted::refusal($this->rm, $w))->toContain('not given consent');
});

it('shows a manager only their own members, refuses someone else\'s, and counts the work in Staff Activity', function () {
    ($this->ready)();
    // a second assisted member with a different manager
    $tp = $this->bench->m['TP'];
    UserMembership::create(['user_id' => $tp->user_id, 'plan_id' => $this->plan->id, 'starts_at' => now(), 'ends_at' => now()->addMonths(3), 'is_active' => true]);
    ($this->asStaff)($this->owner);
    Assisted::setManager($tp, $this->otherRm);

    ($this->asStaff)($this->rm);
    Livewire::test(AssistedMembers::class)
        ->assertSee($this->t->matri_id)->assertDontSee($tp->matri_id)
        ->set('mine', false)->assertDontSee($tp->matri_id);              // whatever the browser sends
    Livewire::test(AssistedMembers::class)->call('open', $tp->id)->assertForbidden();

    Livewire::test(AssistedMembers::class)->call('open', $this->t->id)
        ->set('suggestId', $this->bench->m['V']->matri_id)->set('suggestNote', 'Good family')->call('suggest')
        ->assertSee('Good family');
    expect(AdminRecommendation::where('for_profile_id', $this->t->id)->count())->toBe(1);

    $row = collect(StaffActivity::summary(...StaffActivity::range('today')))->firstWhere('staff_id', $this->rm->id);
    expect($row['assisted'])->toBe(1);

    // the owner sees everyone
    ($this->asStaff)($this->owner);
    Livewire::test(AssistedMembers::class)->assertSee($this->t->matri_id)->assertSee($tp->matri_id);
});
