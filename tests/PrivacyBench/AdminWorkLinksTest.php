<?php

use App\Filament\Pages\UnlistedCommunities;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\UpcomingFollowups;
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
| Work lists open the member's edit page (6 Nov 2026): staff call members from the
| follow-up lists and fix castes from the unlisted list, and the edit page has the
| member card, Notes and every field. Staff who may not edit get the view page.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->owner = User::create(['name' => 'Links Owner', 'email' => 'links-owner@bench.test', 'password' => bcrypt('x-links-pass-1'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    $this->member = app(MemberCreationService::class)->create(['full_name' => 'Work Links', 'gender' => 'male', 'date_of_birth' => '1993-03-03',
        'email' => 'work-links@bench.test', 'phone' => '9812300101', 'religion' => 'Hindu', 'caste' => 'Other'], ['temp_password' => 'links-pass-0101'])['profile'];
});

it('opens the edit page on the asked tab for staff who may edit, the view page for staff who may not', function () {
    $p = $this->member;

    $this->actingAs($this->owner, 'admin');
    expect(UserResource::profileWorkUrl($p, 'Religion & Community'))
        ->toBe(UserResource::getUrl('edit', ['record' => $p]) . '?tab=religion-community%3A%3Adata%3A%3Atab')
        ->and(UserResource::profileWorkUrl($p))->toBe(UserResource::getUrl('edit', ['record' => $p]));

    $roleId = DB::table('staff_roles')->insertGetId(['name' => 'bench_reader', 'slug' => 'bench_reader', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 99, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('staff_role_permissions')->insert(['staff_role_id' => $roleId, 'permission_key' => 'view_member', 'scope' => 'all', 'created_at' => now(), 'updated_at' => now()]);
    $reader = User::create(['name' => 'Bench Reader', 'email' => 'bench-reader@bench.test', 'password' => bcrypt('x-reader-pass-1'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => $roleId, 'email_verified_at' => now()]);
    $this->actingAs($reader, 'admin');
    expect(UserResource::profileWorkUrl($p, 'Religion & Community'))->toBe(UserResource::getUrl('view', ['record' => $p]));
});

it('links a typed-in caste to the member\'s Religion & Community tab', function () {
    $this->member->religiousInfo->update(['other_caste_name' => 'Benchcaste']);

    $this->actingAs($this->owner, 'admin');
    Livewire::test(UnlistedCommunities::class)
        ->assertSee('Benchcaste')
        ->assertSeeHtml('/users/' . $this->member->matri_id . '/edit?tab=religion-community%3A%3Adata%3A%3Atab');
});

it('badges Delete Requests with the last 7 days\' deletions only, as the Work Queue counts them', function () {
    $this->actingAs($this->owner, 'admin');
    $before = (int) App\Filament\Pages\DeleteRequests::getNavigationBadge();

    $old = app(MemberCreationService::class)->create(['full_name' => 'Deleted Long Ago', 'gender' => 'female', 'date_of_birth' => '1994-04-04',
        'email' => 'deleted-old@bench.test', 'phone' => '9812300102', 'religion' => 'Hindu'], ['temp_password' => 'links-pass-0102'])['profile'];
    $old->delete();
    $old->forceFill(['deleted_at' => now()->subDays(10)])->saveQuietly();
    $this->member->delete();

    expect((int) App\Filament\Pages\DeleteRequests::getNavigationBadge())->toBe($before + 1)
        ->and(App\Filament\Pages\DeleteRequests::getNavigationBadgeColor())->toBe('gray');
});

it('opens the member\'s edit page from the follow-ups due on the Work Queue', function () {
    ProfileNote::create(['profile_id' => $this->member->id, 'admin_user_id' => $this->owner->id, 'note_type' => 'call',
        'note' => 'Call back about photos', 'follow_up_date' => today()]);

    $this->actingAs($this->owner, 'admin');
    Livewire::withoutLazyLoading()->test(UpcomingFollowups::class)
        ->assertSee('Call back about photos')
        ->assertSeeHtml('/users/' . $this->member->matri_id . '/edit');
});
