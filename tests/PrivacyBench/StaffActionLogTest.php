<?php

use App\Filament\Pages\ChangeMembershipPlan;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Models\AdminActivityLog;
use App\Models\IdProof;
use App\Models\ProfileNote;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Services\CsvExportService;
use App\Services\MemberCreationService;
use App\Support\StaffActivity;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Admin audit point 3 (12 Nov 2026): the staff actions the Activity Log missed —
| plan changes, photo / ID proof / horoscope approvals and rejections, deletions,
| notes added, CSV and database downloads — are now logged, against the member
| they were about, and only when staff did them. The log names the member
| ("Sita Iyer (KM100050)") and can be filtered by date and by member.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->staff = User::create(['name' => 'Log Staff', 'email' => 'log-staff@bench.test', 'password' => bcrypt('x-log-pass-1'), 'role' => 'admin',
        'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    $make = fn (string $name, string $phone) => app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => 'female',
        'date_of_birth' => '1995-05-05', 'email' => strtolower(str_replace(' ', '-', $name)).'@bench.test', 'phone' => $phone,
        'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'log-'.substr($phone, -4)])['profile'];
    $this->m = $make('Log Member', '9812800001');
    $this->other = $make('Log Other', '9812800002');
    $this->photo = fn ($p) => ProfilePhoto::create(['profile_id' => $p->id, 'photo_type' => 'profile', 'storage_driver' => 'public', 'is_primary' => false,
        'is_visible' => true, 'approval_status' => 'pending', 'photo_url' => 'photos/x/full/log.webp']);
    $this->logs = fn (string $action) => AdminActivityLog::where('action', $action)->get();
    AdminActivityLog::query()->delete(); // what creating the test members logged
});

it('logs photo, ID proof and horoscope approvals, rejections and deletions by staff, against the member', function () {
    $this->actingAs($this->staff, 'admin');
    $p1 = ($this->photo)($this->m);
    $p2 = ($this->photo)($this->m);
    $p1->update(['approval_status' => 'approved', 'approved_by' => $this->staff->id, 'approved_at' => now()]);
    $p2->update(['approval_status' => 'rejected', 'rejection_reason' => 'Unclear photo']);
    $p2->update(['is_visible' => false]);       // not a status change: not logged again
    $p2->delete();
    $id = IdProof::create(['profile_id' => $this->m->id, 'document_type' => 'Aadhaar', 'document_number' => 'X1', 'document_url' => 'id/x.jpg', 'verification_status' => 'pending']);
    $id->update(['verification_status' => 'approved']);
    $id->update(['verification_status' => 'rejected']);
    DB::table('religious_info')->where('profile_id', $this->m->id)->update(['jathakam_approval_status' => 'pending']);
    $this->m->religiousInfo()->first()->update(['jathakam_approval_status' => 'approved']);

    foreach (['photo_approved', 'photo_rejected', 'photo_deleted', 'id_proof_approved', 'id_proof_rejected', 'document_approved'] as $action) {
        $log = ($this->logs)($action);
        expect($log)->toHaveCount(1, $action)
            ->and($log->first()->admin_user_id)->toBe($this->staff->id)
            ->and($log->first()->model_type)->toBe('Profile')
            ->and($log->first()->model_id)->toBe($this->m->id);
    }
    expect(($this->logs)('photo_rejected')->first()->changes['reason'])->toBe('Unclear photo')
        ->and(($this->logs)('photo_approved')->first()->changes)->not->toHaveKey('reason')
        ->and(($this->logs)('id_proof_approved')->first()->changes['document_type'])->toBe('Aadhaar');

    // Staff Activity counts them
    $row = collect(StaffActivity::summary(...StaffActivity::range('today')))->firstWhere('staff_id', $this->staff->id);
    expect($row['id_checks'])->toBe(3)->and($row['photos_rejected'])->toBe(1);
});

it('saves the horoscope / baptism approval fields for staff, but never from a member\'s own form', function () {
    // 12 Nov 2026: these fields were not fillable, so Approve / Reject saved nothing
    $this->actingAs($this->staff, 'admin');
    $this->m->religiousInfo->update(['jathakam_approval_status' => 'rejected', 'jathakam_rejection_reason' => 'Unclear', 'jathakam_approved_by' => $this->staff->id]);
    $row = DB::table('religious_info')->where('profile_id', $this->m->id)->first();
    expect($row->jathakam_approval_status)->toBe('rejected')->and($row->jathakam_rejection_reason)->toBe('Unclear');

    // the member edits their religion section and tries to approve it themselves
    app('auth')->forgetGuards();
    $this->actingAs($this->m->user->fresh(), 'web');
    $this->m->forceFill(['onboarding_completed' => true])->save();
    $this->post('/profile/religious', ['religion' => 'Hindu', 'caste' => 'Billava', 'jathakam_approval_status' => 'approved', 'jathakam_approved_by' => $this->m->user_id])->assertRedirect();
    $row = DB::table('religious_info')->where('profile_id', $this->m->id)->first();
    expect($row->jathakam_approval_status)->toBe('rejected')->and((int) $row->jathakam_approved_by)->toBe($this->staff->id);
});

it('logs nothing when a member or a scheduled job changes the same records', function () {
    $this->actingAs($this->m->user, 'web');           // the member on the member site
    $p = ($this->photo)($this->m);
    $p->update(['approval_status' => 'approved']);
    $p->delete();

    auth()->forgetGuards();
    app('auth')->shouldUse('web');                    // no one signed in (a job)
    ($this->photo)($this->other)->update(['approval_status' => 'rejected']);

    expect(AdminActivityLog::count())->toBe(0);
});

it('logs notes added, but not the Change Plan page\'s own note, and logs the plan change itself', function () {
    $this->actingAs($this->staff, 'admin');
    ProfileNote::create(['profile_id' => $this->m->id, 'admin_user_id' => $this->staff->id, 'note_type' => 'call', 'note' => 'Called, will send photo', 'follow_up_date' => now()->addDays(2)]);

    $plan = \App\Models\MembershipPlan::query()->where('is_active', true)->orderBy('id')->firstOrFail();
    Livewire::test(ChangeMembershipPlan::class)
        ->set('search', $this->m->matri_id)->call('lookupUser')
        ->call('startAssign')
        ->set('plan_id', (string) $plan->id)->set('payment_method', 'gpay')->set('reason', 'Paid at office')
        ->call('assignPlan');

    $note = ($this->logs)('note_added');
    $planLog = ($this->logs)('plan_assigned');
    expect($note)->toHaveCount(1)                                   // the plan page's own note is not a second one
        ->and($note->first()->changes['type'])->toBe('call')
        ->and($note->first()->changes['follow_up'])->toBe(now()->addDays(2)->toDateString())
        ->and($planLog)->toHaveCount(1)
        ->and($planLog->first()->model_id)->toBe($this->m->id)
        ->and($planLog->first()->changes['plan'])->toBe($plan->plan_name)
        ->and($planLog->first()->changes['payment'])->toBe(ChangeMembershipPlan::PAYMENT_METHODS['gpay'])
        ->and($planLog->first()->changes['reason'])->toBe('Paid at office');
});

it('logs every CSV download with the file and row count', function () {
    $this->actingAs($this->staff, 'admin');
    CsvExportService::exportUsers();
    CsvExportService::exportInterests();

    $logs = ($this->logs)('csv_exported');
    expect($logs)->toHaveCount(2)
        ->and($logs->first()->admin_user_id)->toBe($this->staff->id)
        ->and($logs->first()->changes['file'])->toContain('.csv')
        ->and($logs->first()->changes['rows'])->toBeGreaterThanOrEqual(2);
});

it('names the member in the Activity Log and filters by date and by member', function () {
    $this->actingAs($this->staff, 'admin');
    ($this->photo)($this->m)->update(['approval_status' => 'approved']);
    ($this->photo)($this->other)->update(['approval_status' => 'approved']);
    $old = AdminActivityLog::where('model_id', $this->other->id)->first();
    DB::table('admin_activity_log')->where('id', $old->id)->update(['created_at' => now()->subDays(10)]);
    $this->other->delete(); // a deleted member is still named

    $all = Livewire::test(ListActivityLogs::class);
    $all->assertSee('Log Member ('.$this->m->matri_id.')')
        ->assertSee('Log Other ('.$this->other->matri_id.') — deleted')
        ->assertDontSee('Profile #'.$this->m->id);

    Livewire::test(ListActivityLogs::class)
        ->filterTable('when', ['from' => now()->subDays(2)->toDateString(), 'until' => now()->toDateString()])
        ->assertSee($this->m->matri_id)->assertDontSee($this->other->matri_id);

    Livewire::test(ListActivityLogs::class)
        ->filterTable('member', ['member' => $this->other->matri_id])
        ->assertSee($this->other->matri_id)->assertDontSee('Log Member ('.$this->m->matri_id.')');
});
