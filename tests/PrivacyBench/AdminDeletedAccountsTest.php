<?php

use App\Filament\Pages\DeleteRequests;
use App\Filament\Pages\FollowUpReport;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\User;
use App\Services\MemberCreationService;
use App\Support\Scorecard;
use App\Support\WorkQueue;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Members who delete their own account (6 Nov 2026). On the website the profile stays,
| inactive and hidden, until its details are erased 90 days later. A member who had just
| deleted the account showed as APPROVED in All Members (35 such members on Kudla); the
| Deleted tab and the Delete Requests badge missed them; their follow-ups stayed in the
| list to call. Owner's choice: they are listed only under Deleted, marked DELETED.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->staff = User::create(['name' => 'Deleted Staff', 'email' => 'deleted-staff@bench.test', 'password' => bcrypt('x-deleted-pass-3'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $make = fn (string $name, string $email, string $phone): Profile => app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => 'male',
        'date_of_birth' => '1990-11-30', 'email' => $email, 'phone' => $phone, 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'del-pass-' . substr($phone, -4)])['profile'];
    $this->gone = $make('Gone Member', 'gone-member@bench.test', '9812400001');
    $this->kept = $make('Kept Member', 'kept-member@bench.test', '9812400002');
    $this->off = $make('Off Member', 'off-member@bench.test', '9812400003');
    $this->off->update(['is_active' => false]);
});

/** The member deletes the account on the website, the way Test Oswin did on Kudla. */
function deleteOwnAccount(Profile $profile): Profile
{
    test()->actingAs($profile->user)
        ->post('/settings/delete', ['reason' => 'My Marriage is Fixed'])
        ->assertRedirect('/');
    auth()->logout();

    return $profile->fresh();
}

it('lists a member who deleted the account on the website only under Deleted, marked DELETED', function () {
    $gone = deleteOwnAccount($this->gone);
    expect($gone->account_deleted_at)->not->toBeNull()
        ->and($gone->is_approved)->toBeTrue()          // what the list used to show
        ->and($gone->trashed())->toBeFalse();
    $staffDeleted = $this->kept->replicate(); // a second control: deleted by staff (soft-deleted)
    $staffDeleted = app(MemberCreationService::class)->create(['full_name' => 'Staff Deleted', 'gender' => 'male', 'date_of_birth' => '1991-01-01',
        'email' => 'staff-deleted@bench.test', 'phone' => '9812400004', 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'del-pass-0004'])['profile'];
    $staffDeleted->delete();

    $this->actingAs($this->staff, 'admin');
    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->kept, $this->off])
        ->assertCanNotSeeTableRecords([$gone]);
    Livewire::test(ListUsers::class)->set('activeTab', 'deactivated')
        ->assertCanSeeTableRecords([$this->off])
        ->assertCanNotSeeTableRecords([$gone]);
    Livewire::test(ListUsers::class)->set('activeTab', 'deleted')
        ->assertCanSeeTableRecords([$gone, $staffDeleted])
        ->assertCanNotSeeTableRecords([$this->kept, $this->off])
        ->assertSeeText('DELETED')
        ->assertDontSeeText('APPROVED');

    expect(UserResource::listStatus($gone))
        ->label->toBe('DELETED')
        ->note->toStartWith('Deleted by the member on ' . now()->displayTz()->format('j M Y'))
        ->and(UserResource::listStatus(Profile::withTrashed()->find($staffDeleted->id))['note'])->toStartWith('Deleted by staff on')
        ->and(UserResource::listStatus($this->off->fresh())['label'])->toBe('DEACTIVATED')
        ->and(UserResource::listStatus($this->kept->fresh())['label'])->toBe('APPROVED');
});

it('says on the member\'s page that the member deleted the account, and when the details go', function () {
    $gone = deleteOwnAccount($this->gone);
    $erased = $gone->account_deleted_at->copy()->addDays(90)->displayTz()->format('j M Y');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(ViewUser::class, ['record' => $gone->getRouteKey()])
        ->assertSeeText('Account deleted')
        ->assertSeeText("details are erased on {$erased} unless the account is activated again");
    Livewire::test(ViewUser::class, ['record' => $this->kept->getRouteKey()])
        ->assertDontSeeText('Account deleted');
});

it('counts the member\'s own deletion on Delete Requests, and leaves their follow-ups out of the lists to call', function () {
    foreach ([$this->gone, $this->kept] as $member) {
        ProfileNote::create(['profile_id' => $member->id, 'admin_user_id' => $this->staff->id, 'note_type' => 'call',
            'note' => 'Call back about matches', 'follow_up_date' => today()->subDays(2)]);
    }
    $this->actingAs($this->staff, 'admin');
    $followUpsBefore = collect(WorkQueue::items())->firstWhere('key', 'followups')['count'];

    deleteOwnAccount($this->gone);
    $this->actingAs($this->staff, 'admin');

    expect(DeleteRequests::getNavigationBadge())->toBe('1')
        ->and(collect(WorkQueue::items())->firstWhere('key', 'followups')['count'])->toBe($followUpsBefore - 1)
        ->and(collect(Scorecard::rows())->firstWhere('key', 'backlog')['this'])->toBe(1); // Kept's overdue follow-up only

    Livewire::test(DeleteRequests::class)
        ->assertSeeText('Gone Member')
        ->assertSeeText('Deleted by the member on')
        ->assertSeeText('Off Member'); // deactivated by staff: still under Deactivated
    Livewire::test(FollowUpReport::class)
        ->assertSeeText('Kept Member')
        ->assertDontSeeText('Gone Member');
});
