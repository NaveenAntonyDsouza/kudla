<?php

use App\Filament\Actions\ProfileNotesAction;
use App\Filament\Pages\MembersWithoutPhoto;
use App\Filament\Pages\VerificationCalls as VerificationCallsPage;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\AdminActivityLog;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VerificationCall;
use App\Support\AdminModules;
use App\Support\MemberContacts;
use App\Support\VerificationCalls;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Simpler staff screens (owner, 25 Nov 2026), for a new staff member learning from scratch.
| On 13–25 Nov 2026 the calls were typed as plain notes and the calls list never moved (216
| of 216 still "To call"). Now one box (Log call, Log WhatsApp, Notes) writes every contact
| down, and a call on a new member's verification call moves the list. Promised:
|   - "Spoke to them" verifies only with the first tick; without it the call stays open;
|   - one "call again / follow up on" day: the next try while the call is open, a follow-up once closed;
|   - a WhatsApp is recorded after it was sent, never sent from here; "asked for a photo" counts as asked;
|   - Needs a check keeps did-not-match, not-looking and wrong-number calls until a manager checks them;
|   - the member page saves the details with the call, and a field that does not validate never loses the call;
|   - the lighter Telecaller role gets no bulk approve and no delete forever; Add member is one form;
|   - suspension, request-changes and rejection reasons land in the member's notes history.
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
    VerificationCalls::forget();

    $n = 0;
    $make = function (string $name, string $slug) use (&$n) {
        $n++;
        $u = new User;
        $u->forceFill(['name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'password' => bcrypt('x-simple-pass-5'),
            'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', $slug)->value('id'),
            'email_verified_at' => now(), 'phone' => '98450555'.sprintf('%02d', $n)])->save();

        return $u->fresh();
    };
    $this->owner = $make('Simple Owner', 'super_admin');
    $this->tara = $make('Tara Caller', 'telecaller');     // the one person taking calls, as on Kudla
    $this->mohan = $make('Mohan Manager', 'manager');
    $this->support = $make('Sunil Support', 'support_agent');

    $this->as = function (User $u) {
        app('auth')->forgetGuards();
        $this->actingAs($u, 'admin');
        app('auth')->shouldUse('admin');
        VerificationCalls::forget();
    };
    $this->row = fn (string $key) => VerificationCall::where('profile_id', $this->bench->m[$key]->id)->first();
    ($this->as)($this->tara);
    VerificationCalls::sync();
});

it('verifies a call only with the first tick, and otherwise keeps it on the list for another call', function () {
    $t = $this->bench->m['T'];
    expect(($this->row)('T')->assigned_to)->toBe($this->tara->id);

    // spoke to them, but did not verify them: a try, still on her list
    $message = MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'spoke', 'tasks' => ['photo', 'plans'], 'note' => 'Father picked up']);
    $row = ($this->row)('T');
    expect($message)->toContain('Not verified yet')
        ->and($row->status)->toBe('waiting')->and($row->attempts)->toBe(1)->and($row->no_answers)->toBe(0)
        ->and($row->due_at->isFuture())->toBeTrue()
        ->and($t->fresh()->details_checked_at)->toBeNull()
        ->and(ProfileNote::where('profile_id', $t->id)->latest('id')->value('note'))->toContain('spoke to them, not verified yet')->toContain('Father picked up');

    // a day to call again: the call comes back that day at 10
    $day = now('Asia/Kolkata')->addDays(3)->toDateString();
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'spoke', 'note' => 'Busy at work, call on Friday', 'date' => $day]);
    expect(($this->row)('T')->due_at->displayTz()->format('Y-m-d H:i'))->toBe($day.' 10:00');

    // the first tick verifies; a day given then becomes a follow-up on the call's note
    $follow = now('Asia/Kolkata')->addWeek()->toDateString();
    MemberContacts::log($this->tara, $t, ['type' => 'call', 'outcome' => 'spoke', 'tasks' => ['details', 'filled'], 'date' => $follow]);
    $row = ($this->row)('T');
    $note = ProfileNote::where('profile_id', $t->id)->latest('id')->first();
    expect($row->status)->toBe('verified')->and($row->done_by)->toBe($this->tara->id)->and($row->attempts)->toBe(3)
        ->and($t->fresh()->details_checked_by)->toBe($this->tara->id)
        ->and($note->follow_up_date->toDateString())->toBe($follow)
        ->and(VerificationCalls::tasksDone(now()->subDay())['calls'])->toBe(3)
        ->and(VerificationCalls::tasksDone(now()->subDay())['details'])->toBe(1)
        ->and(VerificationCalls::tasksDone(now()->subDay())['photo'])->toBe(1);
});

it('records no answer with the WhatsApp she sent, and keeps the calls a manager must look at until checked', function () {
    $m = $this->bench->m;
    MemberContacts::log($this->tara, $m['T'], ['type' => 'call', 'outcome' => 'no_answer', 'sent_whatsapp' => true]);
    expect(($this->row)('T')->no_answers)->toBe(1)
        ->and(ProfileNote::where('profile_id', $m['T']->id)->where('note_type', 'whatsapp')->latest('id')->value('note'))->toBe('Sent the "we tried to call you" message on WhatsApp.');

    // these need a note; then they wait under Needs a check
    expect(fn () => MemberContacts::log($this->tara, $m['V'], ['type' => 'call', 'outcome' => 'mismatch', 'note' => ' ']))->toThrow(DomainException::class);
    MemberContacts::log($this->tara, $m['V'], ['type' => 'call', 'outcome' => 'mismatch', 'note' => 'Says born 1991']);
    MemberContacts::log($this->tara, $m['VA'], ['type' => 'call', 'outcome' => 'not_looking', 'note' => 'Already settled']);
    MemberContacts::log($this->tara, $m['VS'], ['type' => 'call', 'outcome' => 'wrong_number']);

    $check = fn () => VerificationCalls::tab($this->mohan, 'check')->pluck('profile_id')->sort()->values()->all();
    expect($check())->toBe(collect([$m['V']->id, $m['VA']->id, $m['VS']->id])->sort()->values()->all())
        ->and(fn () => VerificationCalls::markChecked($this->tara, ($this->row)('V')))->toThrow(DomainException::class);   // only a manager

    ($this->as)($this->mohan);
    VerificationCalls::markChecked($this->mohan, ($this->row)('VA'));
    expect($check())->not->toContain($m['VA']->id)
        ->and(ProfileNote::where('profile_id', $m['VA']->id)->latest('id')->value('note'))->toBe('Verification call: checked by Mohan Manager (not looking any more).')
        ->and(AdminActivityLog::where('action', 'verification_checked')->where('model_id', $m['VA']->id)->where('admin_user_id', $this->mohan->id)->count())->toBe(1)
        ->and(AdminActivityLog::where('action', 'note_added')->where('model_id', $m['VA']->id)->where('admin_user_id', $this->mohan->id)->count())->toBe(0)
        ->and(fn () => VerificationCalls::markChecked($this->mohan, ($this->row)('VA')))->toThrow(DomainException::class);   // once
});

it('writes any other call as a note, with one tick for checked details, and keeps the calls-list answers to the calls list', function () {
    $w = $this->bench->m['W'];
    VerificationCall::where('profile_id', $w->id)->delete();                      // joined long before the list
    DB::table('profiles')->where('id', $w->id)->update(['created_at' => now()->subYear()]);
    $w = $w->fresh();

    expect(MemberContacts::outcomesFor($this->tara, $w))->not->toHaveKeys(['not_looking', 'mismatch'])
        ->and(fn () => MemberContacts::log($this->tara, $w, ['type' => 'call', 'outcome' => 'mismatch', 'note' => 'x']))->toThrow(DomainException::class)
        ->and(fn () => MemberContacts::log($this->tara, $w, ['type' => 'call', 'outcome' => 'call_back', 'at' => now()->subHour()->toDateTimeString()]))->toThrow(DomainException::class);

    MemberContacts::log($this->tara, $w, ['type' => 'call', 'outcome' => 'spoke', 'details_checked' => true, 'note' => 'Renewing next month']);
    $note = ProfileNote::where('profile_id', $w->id)->latest('id')->first();
    expect($note->note_type)->toBe('call')
        ->and($note->note)->toBe('Call: spoke to them. Went through their details with them. Renewing next month')
        ->and($w->fresh()->details_checked_by)->toBe($this->tara->id)
        ->and(VerificationCall::where('profile_id', $w->id)->exists())->toBeFalse();

    // busy: the call-back day goes on the Follow-up Report
    MemberContacts::log($this->tara, $w, ['type' => 'call', 'outcome' => 'call_back', 'at' => now()->addDays(2)->toDateTimeString()]);
    $note = ProfileNote::where('profile_id', $w->id)->latest('id')->first();
    expect($note->note)->toStartWith('Call: busy, call back on')->and($note->follow_up_date)->not->toBeNull();
});

it('records a WhatsApp she sent, gives the number and the text to copy, and counts a photo request as asked', function () {
    $t = $this->bench->m['T'];
    expect(MemberContacts::whatsappNumber($t))->toBe('+91'.Bench::phone(1, 3))
        ->and(MemberContacts::readyText($t, 'missed'))->toContain('your new profile')->toContain($t->matri_id)
        ->and(MemberContacts::readyText($t, 'other'))->toBeNull()
        ->and(fn () => MemberContacts::log($this->tara, $t, ['type' => 'whatsapp', 'whatsapp_kind' => 'other', 'note' => '']))->toThrow(DomainException::class);

    MemberContacts::log($this->tara, $t, ['type' => 'whatsapp', 'whatsapp_kind' => 'photo']);
    expect(ProfileNote::where('profile_id', $t->id)->latest('id')->value('note'))->toStartWith(MembersWithoutPhoto::NOTE_PREFIX);

    // no longer on the calls list: the general "we tried to call you"
    ($this->row)('T')->forceFill(['status' => 'verified', 'done_at' => now()])->save();
    $texts = [MemberContacts::readyText($t, 'missed'), MemberContacts::readyText($t, 'link'), MemberContacts::readyText($t, 'photo')];
    expect($texts[0])->not->toContain('new profile');
    foreach ($texts as $text) {
        foreach ($this->bench->m as $other) {
            if ($other->id !== $t->id) {
                expect($text)->not->toContain($other->matri_id);
            }
        }
        foreach ($this->bench->canaries as $canary => $info) {
            expect($text)->not->toContain((string) $canary);
        }
    }
});

it('marks the member\'s follow-up done from the same box, and needs a note for anything that is not a call', function () {
    $t = $this->bench->m['T'];
    $old = ProfileNote::create(['profile_id' => $t->id, 'admin_user_id' => $this->tara->id, 'note_type' => 'call', 'note' => 'Call after Diwali', 'follow_up_date' => now()->subDay()->toDateString()]);
    expect(MemberContacts::pendingFollowUp($t)->id)->toBe($old->id);

    MemberContacts::log($this->tara, $t, ['type' => 'email', 'note' => 'Sent the plans by email', 'complete_follow_up' => true]);
    expect($old->fresh()->follow_up_completed_at)->not->toBeNull()
        ->and($old->fresh()->follow_up_completed_by)->toBe($this->tara->id)
        ->and(MemberContacts::pendingFollowUp($t))->toBeNull()
        ->and(fn () => MemberContacts::log($this->tara, $t, ['type' => 'meeting', 'note' => '']))->toThrow(DomainException::class)
        ->and(fn () => MemberContacts::log($this->tara, $t, ['type' => 'general', 'note' => 'x', 'date' => now()->subDays(2)->toDateString()]))->toThrow(DomainException::class)
        ->and(fn () => MemberContacts::log($this->tara, $t, ['type' => 'fax', 'note' => 'x']))->toThrow(DomainException::class);
});

it('makes the member page one Log call box that saves the details too, and never loses a call to a field that does not validate', function () {
    $t = $this->bench->m['T'];
    Livewire::test(EditUser::class, ['record' => $t->fresh()->getRouteKey()])
        ->assertActionVisible('logCall')
        ->assertActionVisible('logWhatsApp')
        ->assertActionDoesNotExist('vc_done')
        ->assertDontSee('Save and mark checked on call')
        ->assertSee('Ask them to tell you their date of birth')
        ->assertDontSee('on the profile:')                 // the stored date is not shown beside "don't read it out"
        ->assertSee('Recent notes');

    // the call is recorded, and an edit typed on the page is saved with it
    Livewire::test(EditUser::class, ['record' => $t->fresh()->getRouteKey()])
        ->fillForm(['about_me' => 'Edited during the call'])
        ->callAction('logCall', data: ['type' => 'call', 'outcome' => 'no_answer'])
        ->assertHasNoActionErrors()
        ->assertNotified();
    expect(($this->row)('T')->no_answers)->toBe(1)->and($t->fresh()->about_me)->toBe('Edited during the call');

    // a required field left empty: the call is still recorded, the edit is not saved, and she is told
    Livewire::test(EditUser::class, ['record' => $t->fresh()->getRouteKey()])
        ->fillForm(['full_name' => ''])
        ->callAction('logCall', data: ['type' => 'call', 'outcome' => 'no_answer'])
        ->assertNotified('The call is saved, but your changes to the profile are not');
    expect(($this->row)('T')->no_answers)->toBe(2)->and($t->fresh()->full_name)->toBe('Bench T');

    // switched off: today's page comes back
    SiteSetting::setValue(AdminModules::SIMPLE_STAFF_SCREENS, '0');
    Livewire::test(EditUser::class, ['record' => $t->fresh()->getRouteKey()])
        ->assertActionVisible('vc_done')
        ->assertSee('Save and mark checked on call');
});

it('shows a caller her own calls once, records No answer from the list, and hides hand-out controls with one caller', function () {
    DB::table('verification_calls')->update(['due_at' => now()->subDays(5), 'assigned_at' => now()->subDays(5)]);   // all of them late
    VerificationCalls::forget();
    expect(VerificationCalls::tab($this->tara, 'anyone')->count())->toBe(0);   // her late calls are under My calls only

    Livewire::test(VerificationCallsPage::class)
        ->assertSet('tab', 'mine')
        ->assertSee('My calls')
        ->assertDontSee('Anyone can call')
        ->assertDontSee('Given to');

    $row = ($this->row)('T');
    Livewire::test(VerificationCallsPage::class)
        ->callAction('logNoAnswer', data: ['sent_whatsapp' => true], arguments: ['row' => $row->id])
        ->assertNotified();
    expect($row->refresh()->no_answers)->toBe(1)
        ->and(ProfileNote::where('profile_id', $row->profile_id)->where('note_type', 'whatsapp')->count())->toBe(1);

    // a manager sees one Manage calls menu
    ($this->as)($this->mohan);
    Livewire::test(VerificationCallsPage::class)->assertActionVisible('moveCalls')->assertSee('Manage calls');

    // switched off: today's list
    SiteSetting::setValue(AdminModules::SIMPLE_STAFF_SCREENS, '0');
    ($this->as)($this->tara);
    Livewire::test(VerificationCallsPage::class)->assertSee('Anyone can call');
});

it('gives the Telecaller role no bulk approve and no delete forever, and adds a member in one form', function () {
    $t = $this->bench->m['T'];
    Livewire::test(ListUsers::class)
        ->assertTableBulkActionHidden('approveSelected')
        ->assertTableBulkActionHidden('activate')
        ->assertTableBulkActionHidden('deactivate')
        ->assertTableActionVisible('open', $t)
        ->assertTableActionHidden('softDelete', $t)
        ->assertActionVisible('addMember')
        ->assertActionDoesNotExist('registerOnBehalf');

    ($this->as)($this->owner);
    Livewire::test(ListUsers::class)->assertTableBulkActionVisible('approveSelected')->assertTableActionVisible('softDelete', $t);

    // office-run: the profile counts as complete, no email, logged
    ($this->as)($this->tara);
    Livewire::test(ListUsers::class)
        ->callAction('addMember', data: ['full_name' => 'Added Office Member', 'gender' => 'female', 'date_of_birth' => '1997-05-05', 'phone' => '9845099901'])
        ->assertHasNoActionErrors()
        ->assertRedirect();
    $office = Profile::where('full_name', 'Added Office Member')->firstOrFail();
    expect($office->onboarding_completed)->toBeTrue()
        ->and($office->created_by_staff_id)->toBe($this->tara->id)
        ->and($office->registration_source)->toBe('Admin')
        ->and(AdminActivityLog::where('action', 'member_registered_by_staff')->where('model_id', $office->id)->exists())->toBeTrue();
    Mail::assertNotSent(\App\Mail\StaffCreatedMemberWelcomeMail::class);

    // login details emailed: the member finishes their own profile
    Livewire::test(ListUsers::class)
        ->callAction('addMember', data: ['full_name' => 'Added Web Member', 'gender' => 'male', 'date_of_birth' => '1992-01-01', 'phone' => '9845099902', 'email' => 'added.web@bench.test', 'send_login' => true])
        ->assertHasNoActionErrors();
    $web = Profile::where('full_name', 'Added Web Member')->firstOrFail();
    expect($web->onboarding_completed)->toBeFalse()->and($web->onboarding_step_completed)->toBe(1);
    Mail::assertSent(\App\Mail\StaffCreatedMemberWelcomeMail::class);

    // a member staff added needs no verification call
    VerificationCalls::sync();
    expect(VerificationCall::where('profile_id', $office->id)->value('status'))->toBe('staff_added');
});

it('puts suspension, request-changes and rejection reasons in the notes history, without a second log line', function () {
    ($this->as)($this->owner);
    $t = $this->bench->m['T'];
    Livewire::test(ListUsers::class)
        ->callTableAction('suspend', $t, data: ['suspension_reason' => 'Rude to other members'])
        ->assertHasNoTableActionErrors();
    expect(ProfileNote::where('profile_id', $t->id)->latest('id')->value('note'))->toBe('Suspended. Reason (staff only): Rude to other members')
        ->and(AdminActivityLog::where('action', 'note_added')->where('model_id', $t->id)->count())->toBe(0)
        ->and(AdminActivityLog::where('action', 'profile_suspended')->where('model_id', $t->id)->count())->toBe(1);

    $v = $this->bench->m['V'];
    ProfilePhoto::where('profile_id', $v->id)->first()->update(['approval_status' => 'rejected', 'rejection_reason' => 'Group photo']);
    expect(ProfileNote::where('profile_id', $v->id)->latest('id')->value('note'))->toBe('Photo rejected. Told the member: Group photo');
});

it('lets a role read notes without writing them, as Staff Roles says', function () {
    ($this->as)($this->support);   // Support Agent: View Member Notes, not Add Note to Member
    expect(ProfileNotesAction::canView())->toBeTrue()->and(ProfileNotesAction::canAdd())->toBeFalse();
    ($this->as)($this->tara);
    expect(ProfileNotesAction::canView())->toBeTrue()->and(ProfileNotesAction::canAdd())->toBeTrue();
});
