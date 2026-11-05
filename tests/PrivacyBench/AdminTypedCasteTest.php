<?php

use App\Filament\Pages\UnlistedCommunities;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Community;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\User;
use App\Services\MemberCreationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Castes members typed after "Other" (6 Nov 2026). Staff fix them on the edit page's
| Religion & Community tab: picking a listed caste clears the typed one, and a typed
| answer only counts as waiting (Unlisted Communities, Work Queue, members filter)
| while the pick is still "Other". The member card shows the typed answer.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->staff = User::create(['name' => 'Caste Staff', 'email' => 'caste-staff@bench.test', 'password' => bcrypt('x-caste-pass-1'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    $this->listed = collect(Community::getCasteList('Hindu'))->first(fn ($c) => ! ReligiousInfo::isOtherCaste($c));
});

function typedCasteMember(string $email, string $phone, string $caste, ?string $typed): Profile
{
    $p = app(MemberCreationService::class)->create(['full_name' => 'Typed ' . substr($phone, -3), 'gender' => 'female', 'date_of_birth' => '1995-05-05',
        'email' => $email, 'phone' => $phone, 'religion' => 'Hindu'], ['temp_password' => 'typed-pass-' . substr($phone, -4)])['profile'];
    $p->religiousInfo->update(['religion' => 'Hindu', 'caste' => $caste, 'other_caste_name' => $typed]);

    return $p->fresh();
}

it('counts a typed caste as waiting only while the caste is still "Other"', function () {
    $waiting = typedCasteMember('typed-waiting@bench.test', '9812300201', 'Other / Not Listed', 'Benchwaiting');
    $answered = typedCasteMember('typed-answered@bench.test', '9812300202', $this->listed, 'Benchanswered');

    expect(ReligiousInfo::query()->withUnlistedEntry()->pluck('profile_id')->all())
        ->toContain($waiting->id)->not->toContain($answered->id);

    $this->actingAs($this->staff, 'admin');
    Livewire::test(UnlistedCommunities::class)
        ->assertSee('Benchwaiting')
        ->assertDontSee('Benchanswered');
});

it('clears the typed caste when staff pick a listed one, and the card shows the typed answer until then', function () {
    $p = typedCasteMember('typed-fix@bench.test', '9812300203', 'Other / Not Listed', 'Benchtyped');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->getRouteKey()])
        ->assertSee('Hindu, Benchtyped (typed in)')
        ->fillForm(['rel_caste' => $this->listed])
        ->assertFormSet(['rel_other_caste_name' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    $rel = $p->fresh()->religiousInfo;
    expect($rel->caste)->toBe($this->listed)
        ->and($rel->other_caste_name)->toBeNull();
});

it('keeps an older typed caste visible and saved when staff do not touch the caste', function () {
    $p = typedCasteMember('typed-keep@bench.test', '9812300204', $this->listed, 'Benchold');

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->getRouteKey()])
        ->assertFormFieldIsVisible('rel_other_caste_name')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($p->fresh()->religiousInfo->other_caste_name)->toBe('Benchold');
});
