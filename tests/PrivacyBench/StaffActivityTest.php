<?php

use App\Filament\Pages\StaffActivity as StaffActivityPage;
use App\Models\AdminActivityLog;
use App\Models\LoginAsSession;
use App\Models\LoginHistory;
use App\Models\ProfileNote;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MemberCreationService;
use App\Support\StaffActivity;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Staff Activity (12 Nov 2026, owner: "can admin see how many calls staff made,
| how many notes per day?"). Counts what the admin already records, per staff
| member and per day, in the site's own time zone; staff without the Activity
| Log right see only their own figures.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $role = fn (string $slug) => DB::table('staff_roles')->where('slug', $slug)->value('id');
    $staff = fn (string $name, string $slug) => User::create([
        'name' => $name, 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'password' => bcrypt('x-activity-pass-5'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => $role($slug), 'email_verified_at' => now(),
    ]);
    $this->owner = $staff('Activity Owner', 'super_admin');
    $this->ana = $staff('Activity Ana', 'manager');   // the one doing the calls
    $this->bob = $staff('Activity Bob', 'manager');   // a colleague whose figures Ana must not see
    // a role without the Activity Log right, for "My Activity"
    DB::table('staff_role_permissions')->where('staff_role_id', $role('manager'))->where('permission_key', 'view_activity_log')->delete();

    $make = fn (string $name, string $phone) => app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => 'female',
        'date_of_birth' => '1995-05-05', 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'phone' => $phone,
        'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'activity-'.substr($phone, -4)])['profile'];
    $this->m1 = $make('Activity One', '9812700001');
    $this->m2 = $make('Activity Two', '9812700002');
    $this->m3 = $make('Activity Three', '9812700003');

    // "now" is 3 PM on Wed 11 Nov 2026 in the site's time zone
    $this->tz = StaffActivity::tz();
    $this->travelTo(CarbonImmutable::parse('2026-11-11 15:00', $this->tz));
    $this->at = fn (string $local) => CarbonImmutable::parse($local, $this->tz)->setTimezone(config('app.timezone'));
    $this->note = function (User $by, $member, string $type, string $local, array $extra = []) {
        $n = ProfileNote::create(['profile_id' => $member->id, 'admin_user_id' => $by->id, 'note_type' => $type, 'note' => "{$type} note"] + $extra);
        DB::table('profile_notes')->where('id', $n->id)->update(['created_at' => ($this->at)($local)]);

        return $n;
    };
});

afterEach(fn () => $this->travelBack());

it('counts calls, WhatsApp and members contacted per staff member, on the site\'s own days', function () {
    $n = $this->note;
    $n($this->ana, $this->m1, 'call', '2026-11-11 09:10');
    $n($this->ana, $this->m1, 'call', '2026-11-11 11:00');       // same member again: 2 calls, 1 member
    $n($this->ana, $this->m2, 'whatsapp', '2026-11-11 12:30');
    $n($this->ana, $this->m3, 'meeting', '2026-11-11 23:40');     // late evening still counts as the 11th
    $n($this->ana, $this->m3, 'general', '2026-11-11 13:00');     // a plain note: not a contact
    $n($this->ana, $this->m2, 'call', '2026-11-10 17:00');        // yesterday
    $n($this->ana, $this->m2, 'call', '2026-11-12 00:20');        // tomorrow (just after midnight)
    $n($this->bob, $this->m1, 'call', '2026-11-11 10:00');
    // the Change Plan page writes its own note: a plan change, not a contact
    ProfileNote::create(['profile_id' => $this->m1->id, 'admin_user_id' => $this->ana->id, 'note' => 'Plan set to Gold (3 months) via GPay']);

    [$from, $to] = StaffActivity::range('today');
    $rows = collect(StaffActivity::summary($from, $to))->keyBy('staff_id');
    $ana = $rows[$this->ana->id];

    expect($ana['calls'])->toBe(2)
        ->and($ana['whatsapp'])->toBe(1)
        ->and($ana['other_contacts'])->toBe(1)
        ->and($ana['general_notes'])->toBe(1)
        ->and($ana['members_contacted'])->toBe(3)
        ->and($ana['days_active'])->toBe(1)
        ->and($rows[$this->bob->id]['calls'])->toBe(1)
        ->and($rows[$this->owner->id]['total'])->toBe(0)
        // busiest first
        ->and(collect(StaffActivity::summary($from, $to))->first()['staff_id'])->toBe($this->ana->id);

    [$from, $to] = StaffActivity::range('this_week'); // Mon 9 – Wed 11 Nov
    $week = collect(StaffActivity::summary($from, $to))->firstWhere('staff_id', $this->ana->id);
    expect($week['calls'])->toBe(3)->and($week['days_active'])->toBe(2);

    // day by day, with the first and last thing done that day
    $days = collect(StaffActivity::daily($this->ana->id, ...StaffActivity::range('this_week')))->keyBy('day');
    expect($days->keys()->all())->toBe(['2026-11-11', '2026-11-10'])
        ->and($days['2026-11-11']['calls'])->toBe(2)
        ->and($days['2026-11-11']['first'])->toBe('9:10 AM')
        ->and($days['2026-11-11']['last'])->toBe('11:40 PM');
});

it('counts follow-ups, member work, plans, photos, login-as visits and sign-ins', function () {
    $n = $this->note;
    $n($this->ana, $this->m1, 'call', '2026-11-11 10:00', ['follow_up_date' => '2026-11-14']);       // set
    $onTime = $n($this->bob, $this->m2, 'call', '2026-11-05 10:00', ['follow_up_date' => '2026-11-11']);
    $late = $n($this->bob, $this->m3, 'call', '2026-11-04 10:00', ['follow_up_date' => '2026-11-09']);
    foreach ([$onTime, $late] as $f) {   // Ana marks both done today
        DB::table('profile_notes')->where('id', $f->id)->update(['follow_up_completed_at' => ($this->at)('2026-11-11 14:00'), 'follow_up_completed_by' => $this->ana->id]);
    }
    foreach (['member_details_checked', 'member_details_checked', 'member_profile_updated', 'profile_approved', 'site_settings_updated'] as $action) {
        AdminActivityLog::create(['admin_user_id' => $this->ana->id, 'action' => $action, 'model_type' => 'Profile', 'model_id' => $this->m1->id]);
    }
    Subscription::withoutEvents(fn () => Subscription::create(['user_id' => $this->m1->user_id, 'plan_id' => '1', 'plan_name' => 'Gold', 'amount' => 100,
        'gateway' => 'gpay', 'payment_status' => 'paid', 'gateway_metadata' => ['manual' => true, 'assigned_by_admin_id' => $this->ana->id]]));
    foreach ([now()->subHour(), now()->subDays(3)] as $when) { // one today, one earlier this week
        \App\Models\ProfilePhoto::create(['profile_id' => $this->m2->id, 'photo_type' => 'profile', 'storage_driver' => 'public', 'is_primary' => false,
            'is_visible' => true, 'approval_status' => 'approved', 'photo_url' => 'photos/x/full/activity.webp', 'approved_by' => $this->ana->id, 'approved_at' => $when]);
    }
    $photos = 1;
    LoginAsSession::create(['staff_user_id' => $this->ana->id, 'member_user_id' => $this->m1->user_id, 'matri_id' => $this->m1->matri_id, 'reason' => 'test visit',
        'started_at' => now()->subMinutes(20), 'expires_at' => now()->addMinutes(10), 'ended_at' => now()->subMinutes(8), 'ended_how' => 'exit']);
    LoginHistory::create(['user_id' => $this->ana->id, 'login_method' => 'admin', 'logged_in_at' => now()->subHours(6)]);

    $ana = collect(StaffActivity::summary(...StaffActivity::range('today')))->firstWhere('staff_id', $this->ana->id);
    expect($ana['followups_set'])->toBe(1)
        ->and($ana['followups_done'])->toBe(2)
        ->and($ana['followups_late'])->toBe(1)
        ->and($ana['details_checked'])->toBe(2)
        ->and($ana['member_edits'])->toBe(1)
        ->and($ana['approvals'])->toBe(1)
        ->and($ana['plans_assigned'])->toBe(1)
        ->and($ana['photos_approved'])->toBe($photos)
        ->and($ana['login_as'])->toBe(1)
        ->and($ana['login_as_minutes'])->toBe(12)
        ->and($ana['sign_ins'])->toBe(1);
    // Bob wrote the follow-up notes days ago; doing them is Ana's work
    expect(collect(StaffActivity::summary(...StaffActivity::range('today')))->firstWhere('staff_id', $this->bob->id)['followups_done'])->toBe(0);
});

it('shows everyone to the owner, and only their own figures to other staff', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ($this->note)($this->ana, $this->m1, 'call', '2026-11-11 09:00');
    ($this->note)($this->bob, $this->m2, 'call', '2026-11-11 10:00');
    DB::table('profile_notes')->where('admin_user_id', $this->bob->id)->update(['note' => 'BOBSECRETNOTE call back']);

    $this->actingAs($this->owner, 'admin');
    expect(StaffActivityPage::getNavigationLabel())->toBe('Staff Activity');
    Livewire::test(StaffActivityPage::class)
        ->assertSee('Activity Ana')->assertSee('Activity Bob')
        ->call('pick', $this->bob->id)
        ->assertSee('BOBSECRETNOTE')->assertSee($this->m2->matri_id);

    $this->actingAs($this->ana, 'admin');
    expect(StaffActivityPage::canAccess())->toBeTrue()
        ->and(StaffActivityPage::getNavigationLabel())->toBe('My Activity');
    Livewire::test(StaffActivityPage::class)
        ->assertSee('Activity Ana')->assertDontSee('Activity Bob')
        ->set('staffId', $this->bob->id)          // a hand-edited request
        ->call('pick', $this->bob->id)
        ->assertDontSee('Activity Bob')->assertDontSee('BOBSECRETNOTE')->assertDontSee($this->m2->matri_id);

    // members never reach it
    $this->actingAs($this->m1->user, 'admin');
    expect(StaffActivityPage::canAccess())->toBeFalse();
});

it('reads the period buttons and picked dates safely', function () {
    [$from, $to] = StaffActivity::range('custom', '2026-11-20', '2026-11-01'); // backwards: swapped
    expect($from->toDateString())->toBe('2026-11-01')->and($to->toDateString())->toBe('2026-11-20');
    [$from] = StaffActivity::range('custom', 'not a date', null);
    expect($from->toDateString())->toBe('2026-11-11');
    [$from, $to] = StaffActivity::range('last_month');
    expect($from->toDateString())->toBe('2026-10-01')->and($to->toDateString())->toBe('2026-10-31');
    [$from] = StaffActivity::range('custom', '2020-01-01', '2026-11-11'); // capped to a year
    expect($from->toDateString())->toBe('2025-11-10');
    [$from, $to] = StaffActivity::range('anything');
    expect($from->toDateString())->toBe('2026-11-11')->and($to->toDateString())->toBe('2026-11-11');
});
