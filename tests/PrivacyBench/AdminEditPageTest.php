<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\AdminActivityLog;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\User;
use App\Services\MemberCreationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Admin member edit page restructure, 5 Nov 2026: staff check and update a member's
| details during a phone call. Save keeps them on the page; every Save is logged with
| the fields it changed (contact details without their values); the completion % follows
| the edit; the status changes are header buttons with a confirm, each needing its own
| staff right, and a later Save never undoes them; the page itself needs "Edit Member".
| 6 Nov 2026: "Save and mark checked on call" records that the details were gone
| through with the member, also when nothing needed changing (staff-only).
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->staff = User::create(['name' => 'Page Staff', 'email' => 'page-staff@bench.test', 'password' => bcrypt('x-staff-pass-3'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function editPageMember(string $name, string $email, string $phone): Profile
{
    return app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => 'female', 'date_of_birth' => '1996-02-02',
        'email' => $email, 'phone' => $phone, 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'page-pass-' . substr($phone, -4)])['profile'];
}

/** A staff member whose role has only the given rights. */
function editPageStaff(string $slug, array $rights): User
{
    $roleId = DB::table('staff_roles')->insertGetId(['name' => $slug, 'slug' => $slug, 'is_system' => 0, 'is_active' => 1, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now()]);
    foreach ($rights as $key => $scope) {
        DB::table('staff_role_permissions')->insert(['staff_role_id' => $roleId, 'permission_key' => $key, 'scope' => $scope, 'created_at' => now(), 'updated_at' => now()]);
    }

    return User::create(['name' => 'Bench ' . $slug, 'email' => $slug . '@bench.test', 'password' => bcrypt('bench-pass-' . $slug),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => $roleId, 'email_verified_at' => now()]);
}

it('stays on the page after Save and logs who changed which fields, contact details without their values', function () {
    $p = editPageMember('Edit Log', 'edit-log@bench.test', '9812300001');
    PartnerPreference::updateOrCreate(['profile_id' => $p->id], ['age_from' => null]);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['full_name' => 'Renamed Member', 'created_by' => 'Father', 'user_phone' => '9812300099', 'cont_whatsapp' => '9812300088', 'pp_age_from' => '25'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNoRedirect();

    $log = AdminActivityLog::where('action', 'member_profile_updated')->where('model_id', $p->id)->sole();
    $changed = $log->changes['changed'];
    fwrite(STDERR, "\n  logged: " . json_encode($changed, JSON_UNESCAPED_UNICODE) . "\n");

    expect($log->admin_user_id)->toBe($this->staff->id)
        ->and($p->fresh()->created_by)->toBe('Father')
        ->and($changed['Basic: full name'])->toBe('Edit Log → Renamed Member')
        ->and($changed['Partner preferences: age from'])->toBe('(empty) → 25')
        ->and($changed['Login: phone'])->toBe('changed')
        ->and($changed['Contact: whatsapp number'])->toBe('changed')
        ->and(json_encode($log->changes))->not->toContain('9812300099')->not->toContain('9812300088')->not->toContain('9812300001');
});

it('works out the completion % again when staff save', function () {
    $p = editPageMember('Completion', 'completion@bench.test', '9812300002');
    $p->forceFill(['profile_completion_pct' => 5])->save();

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['pp_age_from' => '24'])
        ->call('save')
        ->assertHasNoFormErrors();

    $p = $p->fresh();
    expect($p->profile_completion_pct)->toBe($p->calculateCompletion())->toBeGreaterThan(5);
});

it('changes status from the header buttons, logged, and a later Save does not undo it', function () {
    $p = editPageMember('Pending One', 'pending-one@bench.test', '9812300003');
    $p->forceFill(['is_approved' => false])->save();

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['full_name' => 'Pending One Edited'])      // an edit not saved yet
        ->callAction('approve')
        ->assertHasNoActionErrors()
        ->assertFormSet(['full_name' => 'Pending One Edited']) // still on the page after the button
        ->callAction('hide')
        ->call('save')
        ->assertHasNoFormErrors();

    $p = $p->fresh();
    expect($p->is_approved)->toBeTrue()
        ->and($p->is_hidden)->toBeTrue()
        ->and($p->full_name)->toBe('Pending One Edited')
        ->and(AdminActivityLog::where('model_id', $p->id)->whereIn('action', ['profile_approved', 'profile_hidden'])->count())->toBe(2);
});

it('shows each status button only to staff with that right, and the page only to staff who may edit', function () {
    $p = editPageMember('Rights Check', 'rights-check@bench.test', '9812300004');
    $p->forceFill(['is_approved' => false])->save();

    $editor = editPageStaff('bench_editor', ['view_member' => 'all', 'edit_member' => 'all']);
    $this->actingAs($editor, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertActionHidden('approve')
        ->assertActionHidden('deactivate')
        ->assertActionHidden('idVerified')
        ->assertActionVisible('notes');

    $approver = editPageStaff('bench_approver', ['view_member' => 'all', 'edit_member' => 'all', 'approve_member' => 'all']);
    $this->actingAs($approver, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertActionVisible('approve')
        ->assertActionHidden('deactivate');

    $viewer = editPageStaff('bench_viewer', ['view_member' => 'all']);
    $this->actingAs($viewer, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])->assertForbidden();
});

it('clears the bounced-email mark when staff correct the address on a call, and only then', function () {
    $p = editPageMember('Bounced Mail', 'bounced-old@bench.test', '9812300006');
    DB::table('users')->where('id', $p->user_id)->update(['email_undeliverable_at' => now()->subDays(3),
        'email_undeliverable_address' => 'bounced-old@bench.test', 'email_undeliverable_reason' => 'mailbox full']);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['full_name' => 'Bounced Mail Two'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($p->user->fresh()->email_undeliverable_at)->not->toBeNull(); // email untouched: still bouncing

    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['user_email' => 'bounced-new@bench.test'])
        ->call('save')
        ->assertHasNoFormErrors();

    $u = $p->user->fresh();
    expect($u->email)->toBe('bounced-new@bench.test')
        ->and($u->email_undeliverable_at)->toBeNull()
        ->and($u->email_undeliverable_address)->toBeNull()
        ->and(AdminActivityLog::where('action', 'email_undeliverable_cleared')->where('model_id', $u->id)->where('admin_user_id', $this->staff->id)->exists())->toBeTrue();
});

it('shows the member card: Matri ID, what is still empty, the last call', function () {
    $p = editPageMember('Card Member', 'card-member@bench.test', '9812300005');
    ProfileNote::create(['profile_id' => $p->id, 'admin_user_id' => $this->staff->id, 'note_type' => 'call', 'note' => 'Asked about the partner age range']);
    $p->locationInfo->update(['native_place' => 'Kaup', 'native_district' => 'Udupi', 'residing_country' => 'United Arab Emirates']);
    $p->educationDetail->update(['occupation' => 'Engineer', 'working_city' => 'Bengaluru', 'working_district' => 'Bangalore Urban', 'working_country' => 'India']);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertSee($p->matri_id)
        ->assertSee('from Kaup, Udupi · Engineer · works in Bengaluru · lives in United Arab Emirates')
        ->assertSee('Missing, ask on this call')
        ->assertSee("Family: father's name")
        ->assertSee('Asked about the partner age range')
        ->assertSee('9812300005');
});

it('marks the details checked on a call when nothing needed changing, logged, and shows it on the card', function () {
    $p = editPageMember('Checked Call', 'checked-call@bench.test', '9812300008');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])->call('save'); // the completion % settles
    AdminActivityLog::where('model_id', $p->id)->delete();
    $updatedAt = $p->fresh()->updated_at;
    $this->travel(5)->seconds();

    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertSee('Save and mark checked on call')
        ->assertSee('Details not checked on a call yet')
        ->call('saveAndMarkChecked')
        ->assertHasNoFormErrors()
        ->assertNoRedirect()
        ->assertNotified('Marked as checked on a call. Nothing needed changing.')
        ->assertSeeText('Details checked on a call ' . now()->displayTz()->format('j M Y') . ' (today) by Page Staff');

    $p = $p->fresh();
    $log = AdminActivityLog::where('action', 'member_details_checked')->where('model_id', $p->id)->sole();
    expect($p->details_checked_at)->not->toBeNull()
        ->and($p->details_checked_by)->toBe($this->staff->id)
        ->and($p->updated_at->equalTo($updatedAt))->toBeTrue() // a check is not an edit
        ->and($log->admin_user_id)->toBe($this->staff->id)
        ->and($log->changes)->toBe(['fields_changed' => 0])
        ->and(AdminActivityLog::where('action', 'member_profile_updated')->where('model_id', $p->id)->exists())->toBeFalse();
});

it('saves the corrections made on the call with the mark, and marks nothing when the details do not save', function () {
    $p = editPageMember('Checked Edit', 'checked-edit@bench.test', '9812300009');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['full_name' => '']) // required: the save stops
        ->call('saveAndMarkChecked')
        ->assertHasFormErrors(['full_name' => 'required']);
    expect($p->fresh()->details_checked_at)->toBeNull()
        ->and(AdminActivityLog::where('action', 'member_details_checked')->where('model_id', $p->id)->exists())->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['fam_father_name' => 'Checked Father'])
        ->call('saveAndMarkChecked')
        ->assertHasNoFormErrors()
        ->assertNotified('Saved, and marked as checked on a call.');

    // the member had no family or partner-preference row yet: their new rows' own values
    // (sibling counts 0, "exclude same gotra" 0) are not staff edits, only the father's name is
    $p = $p->fresh();
    expect($p->familyDetail->father_name)->toBe('Checked Father')
        ->and($p->details_checked_at)->not->toBeNull()
        ->and(AdminActivityLog::where('action', 'member_details_checked')->where('model_id', $p->id)->sole()->changes)->toBe(['fields_changed' => 1])
        ->and(AdminActivityLog::where('action', 'member_profile_updated')->where('model_id', $p->id)->sole()->changes['changed'])
        ->toBe(['Family: father name' => '(empty) → Checked Father']);
});

it('shows the check on the members list, its filter and the view page', function () {
    $checked = editPageMember('List Checked', 'list-checked@bench.test', '9812300010');
    $unchecked = editPageMember('List Unchecked', 'list-unchecked@bench.test', '9812300011');
    DB::table('profiles')->where('id', $checked->id)->update(['details_checked_at' => now()->subDays(3), 'details_checked_by' => $this->staff->id]);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(ListUsers::class)
        ->filterTable('details_checked_at', true)
        ->assertCanSeeTableRecords([$checked->fresh()])
        ->assertCanNotSeeTableRecords([$unchecked])
        ->assertSee('Checked on call ' . now()->subDays(3)->displayTz()->format('d M Y'))
        ->filterTable('details_checked_at', false)
        ->assertCanSeeTableRecords([$unchecked])
        ->assertCanNotSeeTableRecords([$checked->fresh()]);

    Livewire::test(ViewUser::class, ['record' => $checked->fresh()->getRouteKey()])
        ->assertSee('Details checked on a call')
        ->assertSee(now()->subDays(3)->displayTz()->format('d M Y') . ' by Page Staff');
});

it('keeps the check staff-only: not in the member\'s own app profile, nor in what other members get', function () {
    $p = editPageMember('Staff Only', 'staff-only@bench.test', '9812300012');
    DB::table('profiles')->where('id', $p->id)->update(['details_checked_at' => now(), 'details_checked_by' => $this->staff->id]);
    $viewer = app(MemberCreationService::class)->create(['full_name' => 'Staff Only Viewer', 'gender' => 'male', 'date_of_birth' => '1993-03-03',
        'email' => 'staff-only-viewer@bench.test', 'phone' => '9812300013', 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'page-pass-0013'])['profile'];

    expect($p->fresh()->toArray())->not->toHaveKey('details_checked_at')->not->toHaveKey('details_checked_by');

    $own = $this->actingAs($p->user, 'sanctum')->getJson('/api/v1/profile/me');
    $other = $this->actingAs($viewer->user, 'sanctum')->getJson('/api/v1/profiles/' . $p->matri_id);
    expect($own->getStatusCode())->toBe(200)
        ->and($other->getStatusCode())->toBe(200)
        ->and((string) $other->getContent())->toContain($p->matri_id); // the profile really came back
    foreach ([$own, $other] as $res) {
        expect((string) $res->getContent())->not->toContain('details_checked')->not->toContain('Page Staff');
    }
});

it('asks for the right and logs Approve / Activate on the view page too', function () {
    $p = editPageMember('View Page', 'view-page@bench.test', '9812300007');
    $p->forceFill(['is_approved' => false])->save();

    $this->actingAs(editPageStaff('bench_view_only', ['view_member' => 'all']), 'admin');
    Livewire::test(ViewUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertActionHidden('quickApprove')
        ->assertActionHidden('toggleActive');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(ViewUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->callAction('quickApprove')
        ->callAction('toggleActive');

    $p = $p->fresh();
    expect($p->is_approved)->toBeTrue()
        ->and($p->is_active)->toBeFalse()
        ->and(AdminActivityLog::where('model_id', $p->id)->whereIn('action', ['profile_approved', 'profile_deactivated'])->count())->toBe(2);
});
