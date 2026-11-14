<?php

use App\Filament\Pages\VerificationCalls as VerificationCallsPage;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\ProfileNote;
use App\Models\User;
use App\Models\VerificationCall;
use App\Services\MemberErasure;
use App\Support\StaffActivity;
use App\Support\VerificationCalls;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Verification calls (12 Nov 2026, owner: "our staff has to call every new profile at
| least once for verification"). On the bench: Asha and Ravi (Staff) and Tara
| (Telecaller) take the calls; Mohan is a Manager (moves calls, sees everything, is not
| in the rotation); the owner is never given calls. Promised:
|   - every new member is on the list once, shared out evenly; members who are older
|     than the list, switched off or staff accounts are not; a member staff registered
|     goes to that staff member; branch staff get only their branch;
|   - only the caller it is given to records the call, until it has waited 2 days;
|   - the results: no answer (closed after 3), call back at a later time, details that
|     don't match (with a note, for a manager), all written in the member's history;
|   - the edit page's "Save and mark checked on call" is "verified";
|   - the WhatsApp messages carry only the member's own details.
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
    \App\Models\SiteSetting::setValue(\App\Support\AdminModules::VERIFICATION_CALLS, '1');   // Site Settings → Staff tools
    VerificationCalls::forget();

    $n = 0;
    $make = function (string $name, string $slug, ?int $branch = null) use (&$n) {
        $n++;
        $u = new User;
        $u->forceFill(['name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'password' => bcrypt('x-verify-pass-5'),
            'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', $slug)->value('id'),
            'email_verified_at' => now(), 'phone' => '98450333'.sprintf('%02d', $n), 'branch_id' => $branch])->save();

        return $u->fresh();
    };
    $this->owner = $make('Verify Owner', 'super_admin');
    $this->asha = $make('Asha Caller', 'staff');
    $this->ravi = $make('Ravi Caller', 'staff');
    $this->tara = $make('Tara Caller', 'telecaller');
    $this->mohan = $make('Mohan Manager', 'manager');
    $this->support = $make('Sunil Support', 'support_agent');
    $this->make = $make;

    $this->as = function (User $u) {
        app('auth')->forgetGuards();
        $this->actingAs($u, 'admin');
        app('auth')->shouldUse('admin');
        VerificationCalls::forget();
    };
    $this->row = fn (string $key) => VerificationCall::where('profile_id', $this->bench->m[$key]->id)->first();
    ($this->as)($this->owner);
});

it('gives the right to the roles that make calls, and never puts the owner or a manager in the rotation', function () {
    $scopes = DB::table('staff_role_permissions')->join('staff_roles', 'staff_roles.id', '=', 'staff_role_permissions.staff_role_id')
        ->where('permission_key', 'verification_calls')->pluck('scope', 'staff_roles.slug')->all();

    expect($scopes)->toMatchArray(['staff' => 'yes', 'branch_staff' => 'yes', 'telecaller' => 'yes'])
        ->and($scopes)->not->toHaveKey('manager')
        ->and(VerificationCalls::callers()->pluck('id')->all())->toBe([$this->asha->id, $this->ravi->id, $this->tara->id])
        ->and(VerificationCalls::canUse($this->mohan))->toBeTrue()     // manages the list
        ->and(VerificationCalls::isCaller($this->mohan))->toBeFalse()
        ->and(VerificationCalls::canUse($this->support))->toBeFalse()
        ->and(VerificationCalls::canUse($this->bench->m['T']->user))->toBeFalse();
});

it('puts every new member on the list once, shared out evenly, and leaves out older, switched-off and checked ones', function () {
    $m = $this->bench->m;
    DB::table('profiles')->where('id', $m['W']->id)->update(['created_at' => now()->subDays(40)]);   // before the list starts
    $m['TL']->forceFill(['is_active' => false])->save();                                            // switched off
    DB::table('profiles')->where('id', $m['TP']->id)->update(['details_checked_at' => now()->addMinute(), 'details_checked_by' => $this->asha->id]);
    ProfileNote::create(['profile_id' => $m['VA']->id, 'admin_user_id' => $this->ravi->id, 'note_type' => 'call', 'note' => 'Called before the list']);
    $m['C']->forceFill(['created_by_staff_id' => $this->ravi->id])->save();                         // Ravi registered C
    $m['TH']->forceFill(['created_by_staff_id' => $this->asha->id, 'registration_source' => 'Admin'])->save();   // Asha added TH from the console

    expect(VerificationCalls::sync())->toBe(8);
    $rows = VerificationCall::all()->keyBy('profile_id');

    expect($rows->has($m['W']->id))->toBeFalse()
        ->and($rows->has($m['TL']->id))->toBeFalse()
        ->and($rows[$m['TP']->id]->status)->toBe('verified')          // already checked on a call
        ->and($rows[$m['TP']->id]->done_by)->toBe($this->asha->id)
        ->and($rows[$m['VA']->id]->attempts)->toBe(1)                  // the earlier call counts as a try
        ->and($rows[$m['C']->id]->assigned_to)->toBe($this->ravi->id)
        // added from the console: no call needed, marked and noted
        ->and($rows[$m['TH']->id]->status)->toBe('staff_added')
        ->and($rows[$m['TH']->id]->done_by)->toBe($this->asha->id)
        ->and(ProfileNote::where('profile_id', $m['TH']->id)->where('note', 'like', 'Verification call: not needed. Profile added by staff (Asha Caller).')->count())->toBe(1)
        ->and($rows->filter->isOpen()->countBy('assigned_to')->sort()->values()->all())->toBe([1, 2, 3])   // Ravi also keeps C, whom he registered
        ->and($rows->pluck('assigned_to')->filter()->unique()->diff([$this->asha->id, $this->ravi->id, $this->tara->id])->all())->toBe([])
        ->and(VerificationCalls::sync())->toBe(0)                       // once per member
        ->and(VerificationCall::count())->toBe(8);

    // the list's start can move back, which adds the older member too
    VerificationCalls::setStartDate($this->mohan, now(StaffActivity::tz())->subDays(45)->toDateString());
    expect(VerificationCall::where('profile_id', $m['W']->id)->exists())->toBeTrue()
        ->and(fn () => VerificationCalls::setStartDate($this->asha, now()->toDateString()))->toThrow(DomainException::class);
});

it('gives branch staff only their own branch, and shows them only that', function () {
    $m = $this->bench->m;
    $north = DB::table('branches')->insertGetId(['name' => 'North Branch', 'code' => 'NTH', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    $south = DB::table('branches')->insertGetId(['name' => 'South Branch', 'code' => 'STH', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('profiles')->whereIn('id', collect($m)->pluck('id'))->update(['branch_id' => $north]);
    DB::table('profiles')->where('id', $m['V']->id)->update(['branch_id' => $south]);
    $this->tara->forceFill(['branch_id' => $south])->save();
    $this->tara = $this->tara->fresh();

    VerificationCalls::sync();
    $taras = VerificationCall::where('assigned_to', $this->tara->id)->pluck('profile_id')->all();

    expect($taras)->toBe([$m['V']->id])
        ->and(VerificationCalls::rows($this->tara)->get()->pluck('profile_id')->all())->toBe([$m['V']->id]);

    // waiting 2+ days opens a call to anyone, but not across branches
    DB::table('verification_calls')->update(['due_at' => now()->subDays(3), 'assigned_at' => now()->subDays(3)]);
    $t = ($this->row)('T');
    expect(VerificationCalls::isForAnyone($t))->toBeTrue()
        ->and(VerificationCalls::mayWork($this->tara, $t))->toBeFalse()
        ->and(VerificationCalls::mayWork($this->asha, $t))->toBeTrue();
});

it('records no answer, call back and details that do not match, in the member\'s history', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    $row = ($this->row)('T');
    $caller = User::find($row->assigned_to);
    ($this->as)($caller);

    expect(VerificationCalls::record($caller, $row, 'no_answer'))->toContain('comes back on the list');
    $row->refresh();
    expect($row->status)->toBe('waiting')->and($row->no_answers)->toBe(1)->and($row->due_at->isFuture())->toBeTrue();

    VerificationCalls::record($caller, $row->refresh(), 'no_answer');
    expect(VerificationCalls::record($caller, $row->refresh(), 'no_answer'))->toContain('could not reach');
    $row->refresh();
    expect($row->status)->toBe('not_reached')->and($row->done_by)->toBe($caller->id)->and($row->attempts)->toBe(3)
        ->and(ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'call')->where('note', 'like', 'Verification call:%')->count())->toBe(3)
        ->and(fn () => VerificationCalls::record($caller, $row, 'no_answer'))->toThrow(DomainException::class);   // finished

    VerificationCalls::reopen($caller, $row);
    expect($row->refresh()->status)->toBe('waiting')->and($row->no_answers)->toBe(0);

    // call back: a time later than now
    expect(fn () => VerificationCalls::record($caller, $row->refresh(), 'call_back', null, now()->subHour()))->toThrow(DomainException::class);
    VerificationCalls::record($caller, $row->refresh(), 'call_back', 'after 6 pm', now()->addDay());
    expect($row->refresh()->status)->toBe('call_back')->and($row->due_at->isFuture())->toBeTrue();

    // details that don't match: a note, then a manager sees it
    expect(fn () => VerificationCalls::record($caller, $row->refresh(), 'mismatch', ' '))->toThrow(DomainException::class);
    VerificationCalls::record($caller, $row->refresh(), 'mismatch', 'Says born 1990, profile says 1996');
    expect($row->refresh()->status)->toBe('mismatch')
        ->and(VerificationCalls::tab($this->mohan, 'check')->get()->pluck('id')->all())->toBe([$row->id])
        ->and(ProfileNote::where('profile_id', $m['T']->id)->latest('id')->value('note'))->toContain('the details did not match')
        ->and(ProfileNote::where('profile_id', $m['T']->id)->latest('id')->value('note'))->toContain('born 1990');

    // Staff Activity counts every try as a call
    $week = [CarbonImmutable::now()->subDay(), CarbonImmutable::now()->addDay()];
    $mine = collect(StaffActivity::summary($week[0], $week[1], $caller->id))->first();
    expect($mine['calls'])->toBe(5);   // 3 no answers, the call back, the mismatch
});

it('lets only the caller it is given to record it, until it has waited 2 days, and lets managers move calls', function () {
    VerificationCalls::sync();
    $row = ($this->row)('T');
    $owner = User::find($row->assigned_to);
    $other = collect([$this->asha, $this->ravi, $this->tara])->first(fn ($u) => $u->id !== $owner->id);

    expect(VerificationCalls::mayWork($other, $row))->toBeFalse()
        ->and(fn () => VerificationCalls::record($other, $row, 'no_answer'))->toThrow(DomainException::class)
        ->and(VerificationCalls::mayWork($this->support, $row))->toBeFalse()
        ->and(VerificationCalls::mayWork($this->mohan, $row))->toBeTrue();

    // the menu badge: a caller's own calls; a manager only those nobody is doing
    VerificationCalls::forget();
    expect(VerificationCalls::dueCount($owner))->toBe(VerificationCall::where('assigned_to', $owner->id)->count());
    VerificationCalls::forget();
    expect(VerificationCalls::dueCount($this->mohan))->toBe(0);

    // a member who joined days ago but was given out today (the list just started) stays with the caller
    DB::table('verification_calls')->where('id', $row->id)->update(['due_at' => now()->subDays(20)]);
    $row->refresh();
    expect(VerificationCalls::isForAnyone($row))->toBeFalse()
        ->and(VerificationCalls::tab($other, 'anyone')->get()->pluck('id')->all())->not->toContain($row->id);

    // left 2 days by its caller: anyone may call, and whoever does takes it over
    DB::table('verification_calls')->where('id', $row->id)->update(['assigned_at' => now()->subDays(3)]);
    $row->refresh();
    VerificationCalls::forget();
    expect(VerificationCalls::dueCount($this->mohan))->toBe(1);
    expect(VerificationCalls::tab($other, 'anyone')->get()->pluck('id')->all())->toContain($row->id);
    VerificationCalls::record($other, $row, 'no_answer');
    expect($row->refresh()->assigned_to)->toBe($other->id);

    // a manager moves one call, or all of someone's calls
    VerificationCalls::assign($this->mohan, $row, $owner->id);
    expect($row->refresh()->assigned_to)->toBe($owner->id)
        ->and(fn () => VerificationCalls::assign($other, $row, $other->id))->toThrow(DomainException::class)          // not a manager
        ->and(fn () => VerificationCalls::assign($this->mohan, $row, $this->mohan->id))->toThrow(DomainException::class); // does not take calls

    $open = VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', $owner->id)->count();
    expect(VerificationCalls::moveAll($this->mohan, $owner->id, null))->toBe($open)
        ->and(VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', $owner->id)->count())->toBe(0);

    // several ticked calls at once: to anyone (unassigned), then to one caller
    $ids = VerificationCall::whereIn('status', VerificationCall::OPEN)->orderBy('id')->limit(3)->pluck('id')->all();
    expect(VerificationCalls::assignMany($this->mohan, $ids, null))->toBe(3)
        ->and(VerificationCall::whereIn('id', $ids)->whereNull('assigned_to')->count())->toBe(3)
        ->and(VerificationCalls::assignMany($this->mohan, $ids, $this->tara->id))->toBe(3)
        ->and(VerificationCalls::assignMany($this->mohan, $ids, $this->tara->id))->toBe(0)       // already hers
        ->and(VerificationCall::whereIn('id', $ids)->pluck('assigned_to')->unique()->values()->all())->toBe([$this->tara->id])
        ->and(fn () => VerificationCalls::assignMany($other, $ids, $other->id))->toThrow(DomainException::class)   // not a manager
        ->and(fn () => VerificationCalls::assignMany($this->mohan, [], null))->toThrow(DomainException::class);
});

it('makes the edit page the call screen: Save and mark checked on call is verified, the rest under Call result', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    $row = ($this->row)('T');
    $caller = User::find($row->assigned_to);
    $other = collect([$this->asha, $this->ravi, $this->tara])->first(fn ($u) => $u->id !== $caller->id);

    // someone else's call: it shows whose it is, and nothing can be recorded
    ($this->as)($other);
    Livewire::test(EditUser::class, ['record' => $m['T']->fresh()->getRouteKey()])
        ->assertSee('Verification call')
        ->assertSee($caller->name.' makes this call.')
        ->assertDontSee('We tried to call you')
        ->call('verificationWhatsApp', 'missed')
        ->assertNotified('You can\'t send messages for this member from here.');
    expect(ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'whatsapp')->exists())->toBeFalse();

    ($this->as)($caller);
    Livewire::test(EditUser::class, ['record' => $m['T']->fresh()->getRouteKey()])
        ->assertSee('Ask them to tell you their date of birth')
        ->assertSee('4 Apr 1996')
        ->assertSee('We tried to call you')
        ->assertSee('Share matches link')
        ->assertDontSee('Ask for a photo')                     // T has a photo
        ->call('verificationWhatsApp', 'missed')
        ->assertNotified('Noted in the member\'s history.')
        ->callAction('vc_no_answer')
        ->assertNotified();
    expect($row->refresh()->no_answers)->toBe(1)
        ->and(ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'whatsapp')->where('note', 'like', 'Verification call:%')->count())->toBe(1);

    Livewire::test(EditUser::class, ['record' => $m['T']->fresh()->getRouteKey()])
        ->call('saveAndMarkChecked')
        ->assertHasNoFormErrors();
    expect($row->refresh()->status)->toBe('verified')
        ->and($row->done_by)->toBe($caller->id)
        ->and($m['T']->fresh()->details_checked_by)->toBe($caller->id)
        // the call that verified her is in her history as a call, like the tries before it
        ->and(ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'call')->where('note', 'like', 'Verification call: details checked%')->count())->toBe(1);
    $week = VerificationCalls::report($this->mohan)['rows']->firstWhere('name', $caller->name);
    expect($week['calls'])->toBe(2)->and($week['verified'])->toBe(1);   // the no answer, then the call that verified

    // a member who joined before the list, checked on a call: verified, not added again later
    DB::table('profiles')->where('id', $m['W']->id)->update(['created_at' => now()->subDays(90)]);
    VerificationCall::where('profile_id', $m['W']->id)->delete();
    VerificationCalls::checkedOnCall($caller, $m['W']->fresh());
    expect(VerificationCall::where('profile_id', $m['W']->id)->value('status'))->toBe('verified');
});

it('lists each caller\'s own calls, hides the others\', and shows managers everything with the week\'s report', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    $byCaller = VerificationCall::with('profile')->get()->groupBy('assigned_to');
    $ashas = $byCaller[$this->asha->id]->map(fn ($r) => $r->profile->full_name);
    $ravis = $byCaller[$this->ravi->id]->map(fn ($r) => $r->profile->full_name);

    ($this->as)($this->asha);
    $page = Livewire::test(VerificationCallsPage::class)->assertSet('tab', 'mine');
    foreach ($ashas as $name) {
        $page->assertSeeHtml('>'.$name.'</a>');
    }
    foreach ($ravis as $name) {
        $page->assertDontSeeHtml('>'.$name.'</a>');
    }
    $page->assertDontSee('This week');

    ($this->as)($this->mohan);
    $page = Livewire::test(VerificationCallsPage::class)->assertSet('tab', 'anyone')->assertSee('This week')->set('tab', 'all');
    foreach ($ashas->merge($ravis) as $name) {
        $page->assertSeeHtml('>'.$name.'</a>');
    }

    // the Work Queue line: all of them for the manager, a caller's own
    expect(collect(\App\Support\WorkQueue::items())->firstWhere('key', 'verification')['count'])->toBe(VerificationCall::count());
    ($this->as)($this->asha);
    expect(collect(\App\Support\WorkQueue::items())->firstWhere('key', 'verification')['count'])->toBe($ashas->count());

    // No answer from the list
    ($this->as)($this->asha);
    $first = $byCaller[$this->asha->id]->first();
    Livewire::test(VerificationCallsPage::class)->call('noAnswer', $first->id)->assertNotified();
    expect($first->refresh()->no_answers)->toBe(1);
    // and not on someone else's
    $theirs = $byCaller[$this->ravi->id]->first();
    Livewire::test(VerificationCallsPage::class)->call('noAnswer', $theirs->id)->assertNotified();
    expect($theirs->refresh()->no_answers)->toBe(0);

    // a manager ticks two calls and gives them to Tara; callers get no tick boxes
    ($this->as)($this->mohan);
    $two = VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', '!=', $this->tara->id)->orderBy('id')->limit(2)->pluck('id');
    Livewire::test(VerificationCallsPage::class)->set('tab', 'all')
        ->assertSee('Give the ticked calls to')
        ->set('picked', $two->map(fn ($id) => (string) $id)->all())
        ->set('giveTo', (string) $this->tara->id)
        ->call('givePicked')
        ->assertNotified('Moved 2 calls.')
        ->assertSet('picked', []);
    expect(VerificationCall::whereIn('id', $two)->pluck('assigned_to')->unique()->values()->all())->toBe([$this->tara->id]);

    ($this->as)($this->asha);
    Livewire::test(VerificationCallsPage::class)
        ->assertDontSee('Give the ticked calls to')
        ->set('picked', [(string) $two->first()])->set('giveTo', (string) $this->asha->id)
        ->call('givePicked')
        ->assertNotified('Only a manager can move calls.');
    expect(VerificationCall::find($two->first())->assigned_to)->toBe($this->tara->id);

    ($this->as)($this->support);
    expect(VerificationCallsPage::canAccess())->toBeFalse();
});

it('writes WhatsApp messages with only the member\'s own details, and erases the call with the member', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    $t = $m['T']->fresh();

    $missed = VerificationCalls::message($t, 'missed');
    $matches = VerificationCalls::message($t, 'matches');
    expect($missed)->toContain('Hi Bench')->toContain($t->matri_id)
        ->and($matches)->toContain('grooms')->toContain(route('matches.index'));
    foreach ([$missed, $matches] as $text) {
        foreach ($m as $other) {
            if ($other->id !== $t->id) {
                expect($text)->not->toContain($other->matri_id);
            }
        }
        foreach ($this->bench->canaries as $canary => $info) {
            expect($text)->not->toContain((string) $canary);
        }
    }

    app(MemberErasure::class)->erase($t);
    expect(VerificationCall::where('profile_id', $t->id)->exists())->toBeFalse();
});

it('skips a caller who is away, shares out their calls, and does nothing while the switch is off', function () {
    VerificationCalls::sync();
    $taras = VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', $this->tara->id)->count();

    expect(fn () => VerificationCalls::setAway($this->asha, $this->tara->id, true))->toThrow(DomainException::class)   // not a manager
        ->and(fn () => VerificationCalls::setAway($this->mohan, $this->tara->id, true, now()->subDays(2)->toDateString()))->toThrow(DomainException::class)
        ->and(VerificationCalls::setAway($this->mohan, $this->tara->id, true, now()->addDays(3)->toDateString(), true))->toBe($taras)
        ->and(VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', $this->tara->id)->count())->toBe(0)
        ->and(VerificationCalls::rotation()->pluck('id')->all())->toBe([$this->asha->id, $this->ravi->id])
        ->and(VerificationCalls::isCaller($this->tara->fresh()))->toBeTrue()                 // she can still work her list
        ->and(VerificationCalls::report($this->mohan)['rows']->firstWhere('id', $this->tara->id)['away'])->toStartWith('Away until');

    // a member who joins while she is away goes to the others
    VerificationCall::where('profile_id', $this->bench->m['W']->id)->delete();
    VerificationCalls::sync();
    expect(($this->row)('W')->assigned_to)->toBeIn([$this->asha->id, $this->ravi->id]);

    // the day after her last day away she is back by herself; or the manager marks her back
    $this->travel(4)->days();
    VerificationCalls::forget();
    expect(VerificationCalls::rotation()->pluck('id')->all())->toContain($this->tara->id);
    $this->travelBack();
    VerificationCalls::setAway($this->mohan, $this->tara->id, false);
    expect(VerificationCalls::rotation()->pluck('id')->all())->toContain($this->tara->id);

    // Site Settings → Staff tools → Verification calls off: no page, no sharing out
    \App\Models\SiteSetting::setValue(\App\Support\AdminModules::VERIFICATION_CALLS, '0');
    VerificationCalls::forget();
    VerificationCall::where('profile_id', $this->bench->m['W']->id)->delete();
    expect(VerificationCalls::canUse($this->mohan))->toBeFalse()
        ->and(VerificationCalls::sync())->toBe(0)
        ->and(VerificationCalls::card($this->mohan, $this->bench->m['T']))->toBeNull()
        ->and(collect(\App\Support\WorkQueue::items())->firstWhere('key', 'verification'))->toBeNull();
});

it('shows the newest members first by default, the longest waiting the other way, and puts calls put off below', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    // every bench member gets its own join date, the first key oldest and the last newest
    $keys = array_keys($m);
    foreach ($keys as $i => $key) {
        DB::table('profiles')->where('id', $m[$key]->id)->update(['created_at' => now()->subDays(20 - $i)]);
    }
    $first = $m[$keys[0]]->id;
    $last = $m[$keys[count($keys) - 1]]->id;
    ($this->as)($this->mohan);

    $order = fn (string $sort) => VerificationCalls::tab($this->mohan, 'all', $sort)->pluck('profile_id')->all();
    $newest = $order('newest');
    $oldest = $order('oldest');

    expect($newest)->toBe(array_reverse($oldest))
        ->and($newest[0])->toBe($last)                       // joined most recently
        ->and($oldest[0])->toBe($first)                      // waiting longest
        // an unknown sort word falls back to newest rather than erroring
        ->and($order('sideways'))->toBe($newest)
        ->and(VerificationCalls::tab($this->mohan, 'all')->pluck('profile_id')->all())->toBe($newest);

    // a call put off to tomorrow drops below the ones due now, whichever order is chosen
    $putOff = VerificationCall::where('profile_id', $last)->first();
    VerificationCalls::record($this->mohan, $putOff, 'call_back', 'after work', now()->addDay());
    expect(array_slice($order('newest'), -1))->toBe([$last])
        ->and(array_slice($order('oldest'), -1))->toBe([$last]);
});

it('records what the staff member says they did on the call, nothing ticked for them', function () {
    VerificationCalls::sync();
    $m = $this->bench->m;
    $row = ($this->row)('T');
    $caller = User::find($row->assigned_to);
    $other = collect([$this->asha, $this->ravi, $this->tara])->first(fn ($u) => $u->id !== $caller->id);
    ($this->as)($caller);

    // nothing is ever pre-ticked: a photo asked for on the staff member's own phone counts
    expect(VerificationCalls::TASKS)->toHaveKeys(['details', 'filled', 'preferences', 'photo', 'members', 'explained', 'link', 'plans'])
        ->and(array_keys(VerificationCalls::TASK_SHORT))->toBe(array_keys(VerificationCalls::TASKS))
        ->and(fn () => VerificationCalls::finishCall($caller, $row, [], ''))->toThrow(DomainException::class)
        ->and(fn () => VerificationCalls::finishCall($other, $row, ['photo'], null))->toThrow(DomainException::class);

    // unknown ticks are dropped; a free note alone is enough
    $message = VerificationCalls::finishCall($caller, $row, ['photo', 'details', 'nonsense'], 'Father will call back on Sunday.');
    expect($message)->toContain('2 things done');

    $row->refresh();
    $note = ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'call')->latest('id')->first();
    expect($row->status)->toBe('verified')
        ->and($row->done_by)->toBe($caller->id)
        ->and($row->attempts)->toBe(1)
        // reads as one sentence: "Verified them by their date of birth and details; asked for a photo on WhatsApp."
        ->and($note->note)->toContain('Verified them by their date of birth and details; asked for a photo')
        ->toContain('Father will call back on Sunday.')
        ->and($note->note)->not->toContain('nonsense')
        // "went through their details" marks the member's card too
        ->and($m['T']->fresh()->details_checked_by)->toBe($caller->id)
        // and it cannot be recorded twice
        ->and(fn () => VerificationCalls::finishCall($caller, $row->refresh(), ['plans'], null))->toThrow(DomainException::class);

    // the ticks are counted for the week, however the staff member did them
    $tasks = VerificationCalls::tasksDone(now()->subDay());
    expect($tasks['calls'])->toBe(1)->and($tasks['photo'])->toBe(1)->and($tasks['details'])->toBe(1)->and($tasks['plans'])->toBe(0);

    // a note-only call is allowed, and Staff Activity counts both as calls
    $second = VerificationCall::whereIn('status', VerificationCall::OPEN)->where('assigned_to', $caller->id)->first();
    VerificationCalls::finishCall($caller, $second, [], 'Wrong time, will ring tomorrow.');
    expect($second->refresh()->status)->toBe('verified')
        ->and(VerificationCalls::tasksDone(now()->subDay())['calls'])->toBe(2);
    $week = [\Carbon\CarbonImmutable::now()->subDay(), \Carbon\CarbonImmutable::now()->addDay()];
    expect(collect(StaffActivity::summary($week[0], $week[1], $caller->id))->first()['calls'])->toBe(2);
});
