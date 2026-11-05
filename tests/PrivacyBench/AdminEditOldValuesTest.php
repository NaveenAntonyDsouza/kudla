<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use App\Services\MemberCreationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Admin member edit page, 5 Nov 2026: members whose stored answers are not in the
| page's dropdown lists (Kudla: "Divorcee" 50, custodian "SELF"…, partner castes from
| before the October list, partner education levels "Bachelor's" 138). Saving such a
| member without touching those fields must neither be refused nor lose the answers.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->staff = User::create(['name' => 'Edit Staff', 'email' => 'edit-staff2@bench.test', 'password' => bcrypt('x-staff-pass-1'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('keeps answers that are not in the dropdown lists when staff save', function () {
    $p = app(MemberCreationService::class)->create(['full_name' => 'Old Values', 'gender' => 'female', 'date_of_birth' => '1994-01-01',
        'email' => 'old-values@bench.test', 'phone' => '9844444444', 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'old-pass-123'])['profile'];
    $p->forceFill(['marital_status' => 'Divorcee'])->save();
    $p->contactInfo->update(['contact_relationship' => 'SELF']);
    App\Models\PartnerPreference::updateOrCreate(['profile_id' => $p->id], ['religions' => ['Hindu'], 'caste' => ['Salian', 'Billava'], 'education_levels' => ["Bachelor's", "Master's"]]);

    $this->actingAs($this->staff, 'admin');
    $test = Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])->call('save');

    $errors = array_keys($test->errors()->toArray());
    $p = $p->fresh();
    fwrite(STDERR, "\n  form errors: " . json_encode($errors) . "\n  after save: " . json_encode([
        'marital_status' => $p->marital_status, 'custodian' => $p->contactInfo->contact_relationship,
        'pp_caste' => $p->partnerPreference->caste, 'pp_education_levels' => $p->partnerPreference->education_levels,
    ]) . "\n");

    expect($errors)->toBe([])
        ->and($p->marital_status)->toBe('Divorcee')
        ->and($p->contactInfo->contact_relationship)->toBe('SELF')
        ->and($p->partnerPreference->caste)->toBe(['Salian', 'Billava'])
        ->and($p->partnerPreference->education_levels)->toBe(["Bachelor's", "Master's"]);
});

it('saves what staff change: time of birth, marital status, differently-abled details, creator, partner education level', function () {
    $p = app(MemberCreationService::class)->create(['full_name' => 'Edits Saved', 'gender' => 'male', 'date_of_birth' => '1990-01-01',
        'email' => 'edits-saved@bench.test', 'phone' => '9866666666', 'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'edit-pass-456'])['profile'];

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm([
            'marital_status' => 'Separated', 'physical_status' => 'Differently Abled', 'da_category' => 'Other', 'da_specify' => 'Low vision',
            'da_description' => 'Wears glasses', 'creator_name' => 'Father Name', 'creator_contact_number' => '9877777777',
            'rel_time_of_birth' => '06:30', 'pp_education_levels' => ["Bachelor's"],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $p = $p->fresh();
    expect($p->marital_status)->toBe('Separated')
        ->and($p->creator_name)->toBe('Father Name')
        ->and($p->creator_contact_number)->toBe('9877777777')
        ->and(substr((string) $p->religiousInfo->time_of_birth, 0, 5))->toBe('06:30')
        ->and($p->differentlyAbledInfo?->only(['category', 'specify', 'description']))->toBe(['category' => 'Other', 'specify' => 'Low vision', 'description' => 'Wears glasses'])
        ->and($p->partnerPreference->education_levels)->toBe(["Bachelor's"]);
});

it('offers state and district dropdowns for India and typed boxes for other countries, as on the member pages', function () {
    $p = app(MemberCreationService::class)->create(['full_name' => 'Abroad', 'gender' => 'male', 'date_of_birth' => '1990-01-01',
        'email' => 'abroad@bench.test', 'phone' => '9888888888', 'religion' => 'Christian'], ['temp_password' => 'edit-pass-789'])['profile'];
    $p->educationDetail->update(['working_country' => 'United Arab Emirates', 'working_state' => 'Dubai', 'working_district' => 'Deira']);
    $p->locationInfo->update(['native_country' => 'India', 'native_state' => 'Karnataka', 'native_district' => 'Udupi']);

    $this->actingAs($this->staff, 'admin');
    // unchanged save keeps the typed Dubai and the listed Udupi
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])->call('save')->assertHasNoFormErrors();
    expect($p->fresh()->educationDetail->only(['working_state', 'working_district']))->toBe(['working_state' => 'Dubai', 'working_district' => 'Deira'])
        ->and($p->fresh()->locationInfo->only(['native_state', 'native_district']))->toBe(['native_state' => 'Karnataka', 'native_district' => 'Udupi']);

    // staff move the job to India and pick from the dropdowns; change the parents' jobs, reference relation, partner ages
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->fillForm(['edu_working_country' => 'India', 'edu_working_state' => 'Karnataka', 'edu_working_district' => 'Bangalore Urban',
            'fam_father_occupation' => config('reference_data.parent_occupation_list')[0], 'cont_reference_relationship' => config('reference_data.reference_relationship_list')[0],
            'pp_age_from' => '24', 'pp_age_to' => '30'])
        ->call('save')->assertHasNoFormErrors();
    $p = $p->fresh();
    expect($p->educationDetail->only(['working_country', 'working_state', 'working_district']))->toBe(['working_country' => 'India', 'working_state' => 'Karnataka', 'working_district' => 'Bangalore Urban'])
        ->and($p->familyDetail->father_occupation)->toBe(config('reference_data.parent_occupation_list')[0])
        ->and((int) $p->partnerPreference->age_from)->toBe(24)
        ->and((int) $p->partnerPreference->age_to)->toBe(30);
});

it('lets staff type an answer that is not in the list, as members can with "Other"', function () {
    $p = app(MemberCreationService::class)->create(['full_name' => 'Typed Other', 'gender' => 'female', 'date_of_birth' => '1995-01-01',
        'email' => 'typed-other@bench.test', 'phone' => '9899999999', 'religion' => 'Christian'], ['temp_password' => 'edit-pass-321'])['profile'];

    $this->actingAs($this->staff, 'admin');
    $page = Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()]);
    $page->callFormComponentAction('fam_father_occupation', 'createOption', ['value' => 'Toddy Tapper'])
        ->callFormComponentAction('cont_custodian_relation', 'createOption', ['value' => 'Godmother'])
        ->call('save')->assertHasNoFormErrors();

    expect($p->fresh()->familyDetail->father_occupation)->toBe('Toddy Tapper')
        ->and($p->fresh()->contactInfo->contact_relationship)->toBe('Godmother');
});
