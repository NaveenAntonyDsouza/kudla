<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
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

it('shows the member card: Matri ID, what is still empty, the last call', function () {
    $p = editPageMember('Card Member', 'card-member@bench.test', '9812300005');
    ProfileNote::create(['profile_id' => $p->id, 'admin_user_id' => $this->staff->id, 'note_type' => 'call', 'note' => 'Asked about the partner age range']);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->assertSee($p->matri_id)
        ->assertSee('Still empty, ask on this call')
        ->assertSee('Asked about the partner age range')
        ->assertSee('9812300005');
});
