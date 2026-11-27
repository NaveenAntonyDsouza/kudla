<?php

use App\Filament\Pages\FollowUpReport;
use App\Filament\Pages\StaffActivity as StaffActivityPage;
use App\Filament\Pages\VerificationCalls as VerificationCallsPage;
use App\Filament\Resources\ContactSubmissionResource;
use App\Filament\Resources\PhotoApprovalResource;
use App\Models\AdminActivityLog;
use App\Models\ContactSubmission;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCall;
use App\Models\WhatsAppMessage;
use App\Support\AdminModules;
use App\Support\MemberContacts;
use App\Support\StaffActivity;
use App\Support\StaffNumbers;
use App\Support\VerificationCalls;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Staff numbers (owner, 27 Nov 2026): Step 1 of the staff analytics plan, with the checks
| ChatGPT's review asked for. Promised:
|   - one call is one call attempt, however many lines saving it writes; two calls to one
|     member are two attempts and one member called; a member is verified once;
|   - each answer is counted from the answer saved on the call note; older notes say
|     "answer not saved", and are never guessed from their text;
|   - WhatsApp written in Notes and WhatsApp sent from the inbox are kept apart; a Contact
|     Inbox reply counts, opening or closing does not; members added count for whoever added them;
|   - actions late at night and just after midnight land on their own days;
|   - every "Waiting now" number is the count of the list it opens, cards only for lists the
|     staff member may open, and members who joined before the start day are shown apart;
|   - times to the first call and to verified: new members only, the middle value;
|   - staff without the Activity Log right see only their own figures; switched off, the
|     page is exactly as before.
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
    SiteSetting::setValue(AdminModules::VERIFICATION_CALLS, '1');
    SiteSetting::setValue(AdminModules::SIMPLE_STAFF_SCREENS, '1');
    SiteSetting::setValue(AdminModules::STAFF_NUMBERS, '1');
    VerificationCalls::forget();

    $n = 0;
    $this->make = function (string $name, string $slug) use (&$n) {
        $n++;
        $u = new User;
        $u->forceFill(['name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'password' => bcrypt('x-numbers-pass-5'),
            'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', $slug)->value('id'),
            'email_verified_at' => now(), 'phone' => '98450777'.sprintf('%02d', $n)])->save();

        return $u->fresh();
    };
    $this->owner = ($this->make)('Numbers Owner', 'super_admin');
    $this->tara = ($this->make)('Tara Numbers', 'telecaller');   // the one person taking calls, as on Kudla

    $this->as = function (User $u) {
        app('auth')->forgetGuards();
        $this->actingAs($u, 'admin');
        app('auth')->shouldUse('admin');
        VerificationCalls::forget();
    };
    $this->row = fn (Profile $p) => VerificationCall::where('profile_id', $p->id)->first();
    ($this->as)($this->tara);
    VerificationCalls::sync();
    // members whose verification call is on Tara's list
    $this->listed = Profile::whereIn('id', VerificationCall::where('assigned_to', $this->tara->id)->pluck('profile_id'))->orderBy('id')->get();
    $this->tz = StaffActivity::tz();
    $this->stored = fn (CarbonImmutable $local) => $local->setTimezone(config('app.timezone'))->toDateTimeString();
});

it('saves each call\'s answer on its note, and counts one call once, one member once and verified once', function () {
    $t = $this->listed[0];
    expect($this->listed->count())->toBeGreaterThanOrEqual(3);

    // three calls on her list: two no answers (the second with the WhatsApp she sent), then verified
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'no_answer']);
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'no_answer', 'sent_whatsapp' => true]);
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'spoke', 'tasks' => ['details']]);
    expect(ProfileNote::where('profile_id', $t->id)->where('note_type', 'call')->orderBy('id')->pluck('outcome')->all())
        ->toBe(['no_answer', 'no_answer', 'spoke'])
        ->and(ProfileNote::where('profile_id', $t->id)->where('note_type', 'whatsapp')->count())->toBe(1)
        // the calls list and the Activity Log also wrote lines: still three calls below
        ->and(AdminActivityLog::where('model_id', $t->id)->where('action', 'like', 'verification_%')->count())->toBe(3);

    // put back on the list and verified again: still one member verified
    VerificationCalls::reopen($this->owner, ($this->row)($t));
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'spoke', 'tasks' => ['details']]);

    // another caller, not on the list for this member: the note keeps the answer too
    $ravi = ($this->make)('Ravi Numbers', 'telecaller');
    MemberContacts::log($ravi, $t, ['type' => 'call', 'outcome' => 'call_back', 'at' => now()->addDay()->toDateTimeString()]);
    MemberContacts::log($ravi, $this->listed[1], ['type' => 'call', 'outcome' => 'wrong_number']);
    expect(ProfileNote::where('admin_user_id', $ravi->id)->orderBy('id')->pluck('outcome')->all())->toBe(['call_back', 'wrong_number']);

    // a call typed in the old Notes box keeps no answer, and none is guessed from its words
    ProfileNote::create(['profile_id' => $this->listed[2]->id, 'admin_user_id' => $this->owner->id, 'note_type' => 'call', 'note' => 'Call: no answer.']);

    [$from, $to] = StaffActivity::range('today');
    $rows = collect(StaffActivity::summary($from, $to))->keyBy('staff_id');
    $tara = $rows[$this->tara->id];
    expect($tara['calls'])->toBe(4)
        ->and($tara['members_called'])->toBe(1)
        ->and($tara['no_answer'])->toBe(2)
        ->and($tara['spoke'])->toBe(2)
        ->and($tara['verified'])->toBe(1)
        ->and($tara['answer_not_saved'])->toBe(0)
        ->and($tara['whatsapp'])->toBe(1)
        ->and($rows[$ravi->id]['call_back'])->toBe(1)
        ->and($rows[$ravi->id]['wrong_number'])->toBe(1)
        ->and($rows[$ravi->id]['members_called'])->toBe(2)
        ->and($rows[$this->owner->id]['answer_not_saved'])->toBe(1)
        ->and($rows[$this->owner->id]['no_answer'])->toBe(0);

    // the team's own figure: member T counts once although two people called her
    $team = StaffActivity::team($from, $to);
    expect($team['calls'])->toBe(7)
        ->and($team['members_called'])->toBe(3)
        ->and($team['verified'])->toBe(1);
});

it('counts members added, Contact Inbox replies but not closes, and keeps inbox WhatsApp apart from WhatsApp in Notes', function () {
    [$w, $v] = [$this->listed[0], $this->listed[1]];
    DB::table('profiles')->where('id', $w->id)->update(['created_by_staff_id' => $this->tara->id]);
    DB::table('profiles')->where('id', $v->id)->update(['created_by_staff_id' => $this->owner->id]);   // the owner's own add stays his
    $v->delete();   // deleted later: the add was still work done

    AdminActivityLog::create(['admin_user_id' => $this->tara->id, 'action' => 'contact_replied', 'model_type' => 'ContactSubmission', 'model_id' => 1]);
    AdminActivityLog::create(['admin_user_id' => $this->tara->id, 'action' => 'contact_closed', 'model_type' => 'ContactSubmission', 'model_id' => 1]);
    foreach ([1, 2] as $i) {
        WhatsAppMessage::create(['direction' => WhatsAppMessage::OUT, 'wa_id' => '91984500000'.$i, 'type' => 'text', 'body' => 'Hello', 'sent_by' => $this->tara->id]);
    }
    WhatsAppMessage::create(['direction' => WhatsAppMessage::IN, 'wa_id' => '919845000001', 'type' => 'text', 'body' => 'Hi']);
    MemberContacts::log($this->tara, $w, ['type' => 'whatsapp', 'whatsapp_kind' => 'photo']);

    [$from, $to] = StaffActivity::range('today');
    $rows = collect(StaffActivity::summary($from, $to))->keyBy('staff_id');
    expect($rows[$this->tara->id]['members_added'])->toBe(1)
        ->and($rows[$this->owner->id]['members_added'])->toBe(1)
        ->and($rows[$this->tara->id]['contact_replies'])->toBe(1)
        ->and($rows[$this->tara->id]['wa_sent'])->toBe(2)
        ->and($rows[$this->tara->id]['whatsapp'])->toBe(1);

    // switched off, none of the new figures is collected
    SiteSetting::setValue(AdminModules::STAFF_NUMBERS, '0');
    $off = collect(StaffActivity::summary($from, $to))->keyBy('staff_id');
    expect($off[$this->tara->id]['members_added'])->toBe(0)
        ->and($off[$this->tara->id]['contact_replies'])->toBe(0)
        ->and($off[$this->tara->id]['wa_sent'])->toBe(0)
        ->and($off[$this->tara->id]['whatsapp'])->toBe(1);
});

it('puts a member verified late at night and one added just after midnight on their own days', function () {
    $today = CarbonImmutable::now($this->tz)->startOfDay();
    [$a, $b] = [$this->listed[0], $this->listed[1]];
    DB::table('verification_calls')->where('profile_id', $a->id)->update(['status' => 'verified', 'done_by' => $this->tara->id,
        'done_at' => ($this->stored)($today->subDay()->setTime(23, 50))]);
    DB::table('profiles')->where('id', $b->id)->update(['created_by_staff_id' => $this->tara->id,
        'created_at' => ($this->stored)($today->setTime(0, 10))]);

    $count = fn (string $range) => collect(StaffActivity::summary(...StaffActivity::range($range)))->firstWhere('staff_id', $this->tara->id);
    expect($count('yesterday')['verified'])->toBe(1)->and($count('yesterday')['members_added'])->toBe(0)
        ->and($count('today')['verified'])->toBe(0)->and($count('today')['members_added'])->toBe(1);

    // last week: Monday to Sunday before this week
    [$from, $to] = StaffActivity::range('last_week');
    expect($from->toDateString())->toBe($today->startOfWeek()->subWeek()->toDateString())
        ->and($to->toDateString())->toBe($today->startOfWeek()->subDay()->toDateString());
});

it('makes each Waiting now number the count of the list it opens, and shows older members apart', function () {
    ($this->as)($this->owner);
    $card = fn (User $u, string $key) => collect(StaffNumbers::waiting($u))->firstWhere('key', $key);
    DB::table('profiles')->update(['created_at' => now()]);   // everyone joined just now
    SiteSetting::setValue('auto_approve_profiles', '1');

    // calls: the calls page's own tab count; a start day of tomorrow makes them all "before"
    SiteSetting::setValue(StaffNumbers::FROM_SETTING, now($this->tz)->addDay()->toDateString());
    $calls = $card($this->owner, 'calls');
    $all = VerificationCalls::tab($this->owner, 'all')->count();
    expect($calls['count'])->toBe($all)->and($all)->toBeGreaterThan(0)
        ->and($calls['url'])->toContain('tab=all')
        ->and(collect($calls['lines'])->pluck(0)->implode(' | '))->toContain($all.' joined before')->not->toContain('longest wait');
    SiteSetting::setValue(StaffNumbers::FROM_SETTING, now($this->tz)->subDay()->toDateString());
    $lines = collect($card($this->owner, 'calls')['lines'])->pluck(0)->implode(' | ');
    expect($lines)->toContain('not called yet: '.$all)->toContain('longest wait for a first call: today')->not->toContain('joined before');
    MemberContacts::log($this->tara, $this->listed[0], ['type' => 'call', 'outcome' => 'no_answer']);
    expect(collect($card($this->owner, 'calls')['lines'])->pluck(0)->implode(' | '))->toContain('not called yet: '.($all - 1));

    // follow-ups: the Follow-up Report's two lists
    ProfileNote::create(['profile_id' => $this->listed[0]->id, 'admin_user_id' => $this->tara->id, 'note_type' => 'general', 'note' => 'ring back', 'follow_up_date' => today()->subDays(3)]);
    ProfileNote::create(['profile_id' => $this->listed[1]->id, 'admin_user_id' => $this->tara->id, 'note_type' => 'general', 'note' => 'ring back', 'follow_up_date' => today()]);
    $follow = $card($this->owner, 'follow_ups');
    $late = FollowUpReport::overdueQuery()->count();
    expect($follow['count'])->toBe($late + FollowUpReport::todayQuery()->count())
        ->and($late)->toBeGreaterThanOrEqual(1)
        ->and($follow['lines'])->toContain(['late: '.$late, 'danger']);

    // Contact Inbox: people waiting, not messages (two from one address)
    $waitingBefore = ContactSubmission::sendersWaiting();
    foreach (['asha@bench.test', 'ASHA@bench.test', 'ravi@bench.test'] as $email) {
        ContactSubmission::create(['name' => 'Bench', 'email' => $email, 'subject' => 'Question', 'message' => 'Hello '.$email, 'status' => 'new']);
    }
    expect($card($this->owner, 'contact_inbox')['count'])->toBe(ContactSubmission::sendersWaiting())->toBe($waitingBefore + 2)
        ->and($card($this->owner, 'photos')['count'])->toBe(ProfilePhoto::where('approval_status', 'pending')->count());

    // profiles to approve: only on a site that approves each profile
    expect($card($this->owner, 'approvals'))->toBeNull();
    SiteSetting::setValue('auto_approve_profiles', '0');
    expect($card($this->owner, 'approvals')['count'])->toBe(Profile::query()->withoutDeletedAccounts()->whereNotNull('full_name')->where('is_approved', false)->count());

    // the caller: her own calls, and only the lists she may open
    ($this->as)($this->tara);
    $hers = collect(StaffNumbers::waiting($this->tara))->keyBy('key');
    expect($hers['calls']['label'])->toBe('Your calls')
        ->and($hers['calls']['count'])->toBe(VerificationCalls::tab($this->tara, 'mine')->count())
        ->and($hers->has('photos'))->toBe(PhotoApprovalResource::canAccess())
        ->and($hers->has('contact_inbox'))->toBe(ContactSubmissionResource::canViewAny());
});

it('times the first call and verified for new members only, as the middle value', function () {
    [$a, $b, $c] = [$this->listed[0], $this->listed[1], $this->listed[2]];
    $now = CarbonImmutable::now($this->tz);
    $set = fn (Profile $p, int $hoursAgo) => DB::table('profiles')->where('id', $p->id)->update(['created_at' => ($this->stored)($now->subHours($hoursAgo))]);
    $call = function (Profile $p, int $hoursAgo) {
        $n = ProfileNote::create(['profile_id' => $p->id, 'admin_user_id' => $this->tara->id, 'note_type' => 'call', 'outcome' => 'no_answer', 'note' => 'Call: no answer.']);
        DB::table('profile_notes')->where('id', $n->id)->update(['created_at' => ($this->stored)(CarbonImmutable::now($this->tz)->subHours($hoursAgo))]);
    };
    $verify = fn (Profile $p, int $hoursAgo) => DB::table('verification_calls')->where('profile_id', $p->id)
        ->update(['status' => 'verified', 'done_by' => $this->tara->id, 'done_at' => ($this->stored)($now->subHours($hoursAgo))]);

    $set($a, 72); $call($a, 20); $verify($a, 1);    // joined before the start day: left out
    $set($b, 10); $call($b, 6); $call($b, 3); $verify($b, 2);   // first call after 4 hours, verified after 8
    $set($c, 5); $call($c, 4); $verify($c, 3);     // first call after 1 hour, verified after 2
    SiteSetting::setValue(StaffNumbers::FROM_SETTING, $now->subDays(2)->toDateString());

    $speed = StaffNumbers::speed(...StaffActivity::range('last_7'));
    expect($speed['first']['n'])->toBe(2)
        ->and($speed['first']['hours'])->toEqualWithDelta(2.5, 0.05)
        ->and($speed['verified']['n'])->toBe(2)
        ->and($speed['verified']['hours'])->toEqualWithDelta(5.0, 0.05)
        ->and(StaffNumbers::duration(2.5))->toBe('3 hours')
        ->and(StaffNumbers::duration(0.4))->toBe('under an hour')
        ->and(StaffNumbers::duration(60))->toBe('2.5 days')
        ->and(StaffNumbers::median([9, 1, 5]))->toBe(5.0);

    // no start day: every member on the list counts
    SiteSetting::setValue(StaffNumbers::FROM_SETTING, '');
    expect(StaffNumbers::speed(...StaffActivity::range('last_7'))['first']['n'])->toBe(3);
});

it('shows the new page to the owner, only her own figures to a caller, and today\'s page when switched off', function () {
    MemberContacts::log($this->tara, $this->listed[0], ['type' => 'call', 'outcome' => 'no_answer']);

    ($this->as)($this->owner);
    Livewire::test(StaffActivityPage::class)
        ->assertSee('Waiting now')->assertSee('What happened on the calls')->assertSee('Time to the first call')
        ->assertSee('Each staff member: calls')->assertSee('Tara Numbers')
        ->set('range', 'last_week')->assertSet('range', 'last_week')->assertSee('Last week')
        ->call('pick', $this->tara->id)->assertSee('Tara Numbers: day by day');

    // a caller without the Activity Log right: her own figures, no team times, nobody else
    ($this->as)($this->tara);
    expect(\App\Support\Permissions::can('view_activity_log'))->toBeFalse();
    Livewire::test(StaffActivityPage::class)
        ->assertSee('Your calls')->assertDontSee('Time to the first call')->assertDontSee('Numbers Owner')
        ->call('pick', $this->owner->id)->assertSet('staffId', $this->tara->id);

    // switched off: today's page, and no "Last week" button
    SiteSetting::setValue(AdminModules::STAFF_NUMBERS, '0');
    ($this->as)($this->owner);
    Livewire::test(StaffActivityPage::class)
        ->assertDontSee('Waiting now')->assertDontSee('What happened on the calls')->assertDontSee('Last week')
        ->assertSee('Each staff member')->assertSee('Members contacted')
        ->set('range', 'last_week')->assertSet('range', 'today');
});

it('lets the calls page box count the days picked', function () {
    $t = $this->listed[0];
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'no_answer']);
    MemberContacts::log($this->tara, $this->listed[1], ['type' => 'call', 'outcome' => 'no_answer']);
    // one of the two calls was yesterday
    DB::table('profile_notes')->where('profile_id', $this->listed[1]->id)->where('note_type', 'call')
        ->update(['created_at' => ($this->stored)(CarbonImmutable::now($this->tz)->subDay()->setTime(12, 0))]);

    ($this->as)($this->owner);
    $calls = fn ($page) => collect($page->viewData('report')['rows'])->firstWhere('id', $this->tara->id)['calls'];
    $page = Livewire::test(VerificationCallsPage::class)->assertSee('Calls: ');
    $page->set('reportRange', 'today');
    expect($calls($page))->toBe(1);
    $page->set('reportRange', 'yesterday');
    expect($calls($page))->toBe(1);
    $page->set('reportRange', 'last_7');
    expect($calls($page))->toBe(2);

    // switched off: "This week" as before
    SiteSetting::setValue(AdminModules::STAFF_NUMBERS, '0');
    Livewire::test(VerificationCallsPage::class)->assertSee('This week')->assertDontSee('Calls: ');
});
