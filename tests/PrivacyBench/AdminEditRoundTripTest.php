<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Profile;
use App\Models\User;
use App\Services\MemberCreationService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\PrivacyBench\Bench;

/*
| Admin member edit page (/console/users/{Matri ID}/edit), checked 5 Nov 2026: staff
| open a member who has a value in every field the page shows and press Save without
| changing anything. Nothing in the member's records may change (no field lost,
| moved or rewritten).
*/

const EDIT_TABLES = ['profiles', 'users', 'religious_info', 'education_details', 'family_details', 'location_info', 'contact_info', 'lifestyle_info', 'social_media_links', 'partner_preferences', 'differently_abled_info'];

function editSnapshot(Profile $p): array
{
    $out = [];
    foreach (EDIT_TABLES as $table) {
        $row = (array) DB::table($table)->where($table === 'users' ? 'id' : ($table === 'profiles' ? 'id' : 'profile_id'), $table === 'users' ? $p->user_id : $p->id)->first();
        // the completion % is worked out again on every Save (AdminEditPageTest)
        unset($row['updated_at'], $row['created_at'], $row['last_login_at'], $row['remember_token'], $row['profile_completion_pct']);
        $out[$table] = $row;
    }

    return $out;
}

/** First option of a reference list (flat or grouped). */
function firstOption(string $key, int $nth = 0): ?string
{
    $flat = collect(config($key, []))->flatMap(fn ($v) => is_array($v) ? array_values($v) : [$v])->values();

    return $flat[$nth] ?? $flat[0] ?? null;
}

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    $this->staff = User::create(['name' => 'Edit Staff', 'email' => 'edit-staff@bench.test', 'password' => bcrypt('x-staff-pass-1'),
        'role' => 'admin', 'is_active' => true, 'staff_role_id' => DB::table('staff_roles')->where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('saves a fully filled member unchanged when staff press Save', function () {
    $p = app(MemberCreationService::class)->create([
        'full_name' => 'Edit Roundtrip', 'gender' => 'female', 'date_of_birth' => '1995-06-15', 'email' => 'edit-rt@bench.test', 'phone' => '9811111111',
        'religion' => 'Christian', 'denomination' => firstOption('reference_data.denomination_list'), 'mother_tongue' => firstOption('reference_data.language_list', 3),
    ], ['temp_password' => 'edit-pass-123'])['profile'];

    $p->forceFill([
        'marital_status' => 'Unmarried', 'height' => firstOption('reference_data.height_list', 30), 'weight_kg' => firstOption('reference_data.weight_list', 20),
        'complexion' => firstOption('reference_data.complexion_list'), 'body_type' => firstOption('reference_data.body_type_list'),
        'blood_group' => firstOption('reference_data.blood_group_list'), 'physical_status' => 'Differently Abled', 'creator_name' => 'Parent Name', 'creator_contact_number' => '9855555555',
        'children_with_me' => 0, 'children_not_with_me' => 0, 'how_did_you_hear_about_us' => firstOption('reference_data.how_did_you_hear_list'),
        'about_me' => 'About me text.', 'is_active' => true, 'is_approved' => true, 'is_hidden' => false, 'id_proof_verified' => true,
    ])->save();
    App\Models\DifferentlyAbledInfo::create(['profile_id' => $p->id, 'category' => 'Other', 'specify' => 'Low vision', 'description' => 'Wears glasses']);
    $p->religiousInfo->update(['diocese' => 'Mangalore', 'parish_name_place' => 'Bejai', 'time_of_birth' => '06:30:00', 'place_of_birth' => 'Mangaluru']);
    $p->educationDetail->update(['highest_education' => firstOption('reference_data.educational_qualifications_list', 5), 'education_level' => firstOption('reference_data.education_level_list'),
        'education_detail' => 'Computer science', 'college_name' => 'St Aloysius', 'occupation' => firstOption('reference_data.occupation_category_list', 4), 'occupation_detail' => 'Developer',
        'employment_category' => firstOption('reference_data.employment_category_list'), 'employer_name' => 'Acme', 'annual_income' => firstOption('reference_data.annual_income_list', 3),
        'working_country' => 'India', 'working_state' => 'Karnataka', 'working_district' => 'Bangalore Urban', 'working_city' => 'Bengaluru']);
    $p->familyDetail->update(['father_name' => 'F', 'father_occupation' => 'Teacher', 'father_house_name' => 'House', 'father_native_place' => 'Udupi',
        'mother_name' => 'M', 'mother_occupation' => 'Nurse', 'mother_house_name' => 'House2', 'mother_native_place' => 'Mangaluru',
        'family_status' => firstOption('reference_data.family_status_list'), 'brothers_married' => 1, 'brothers_unmarried' => 0, 'brothers_priest' => 0,
        'sisters_married' => 0, 'sisters_unmarried' => 1, 'sisters_nun' => 0, 'candidate_asset_details' => 'House', 'about_candidate_family' => 'Nice family']);
    $p->locationInfo->update(['residing_country' => 'India', 'native_country' => 'India', 'native_state' => 'Karnataka', 'native_district' => 'Dakshina Kannada',
        'native_place' => 'Bejai', 'residency_status' => firstOption('reference_data.residency_status_list'), 'pin_zip_code' => '575004',
        'outstation_leave_date_from' => '2026-12-20', 'outstation_leave_date_to' => '2027-01-05']);
    $p->contactInfo->update(['contact_person' => 'Father', 'contact_relationship' => firstOption('reference_data.custodian_relation_list'),
        'preferred_call_time' => firstOption('reference_data.preferred_call_time_list'), 'communication_address' => 'Addr', 'pincode' => '575001',
        'reference_name' => 'Ref', 'reference_mobile' => '9822222222', 'reference_relationship' => 'Uncle', 'secondary_phone' => '9833333333',
        'residential_phone_number' => '0824222222', 'alternate_email' => 'alt@bench.test', 'present_address' => 'Present', 'present_pin_zip_code' => '575002',
        'permanent_address' => 'Permanent', 'permanent_pin_zip_code' => '575003']);
    $p->lifestyleInfo->update(['diet' => firstOption('reference_data.eating_habits'), 'smoking' => firstOption('reference_data.smoking_habits'),
        'drinking' => firstOption('reference_data.drinking_habits'), 'cultural_background' => firstOption('reference_data.cultural_background_list'),
        'hobbies' => [firstOption('reference_data.hobbies_list')], 'interests' => ['Travel'], 'languages_known' => [firstOption('reference_data.language_list')],
        'favorite_music' => [firstOption('reference_data.music_list')], 'preferred_books' => [firstOption('reference_data.books_list')],
        'preferred_movies' => [firstOption('reference_data.movies_list')], 'sports_fitness_games' => [firstOption('reference_data.sports_list')],
        'favorite_cuisine' => [firstOption('reference_data.cuisine_list')]]);
    $p->socialMediaLink->update(['instagram_url' => 'https://instagram.com/x', 'facebook_url' => 'https://facebook.com/x', 'linkedin_url' => 'https://linkedin.com/in/x',
        'youtube_url' => 'https://youtube.com/@x', 'website_url' => 'https://example.com']);
    App\Models\PartnerPreference::updateOrCreate(['profile_id' => $p->id], [
        'age_from' => 25, 'age_to' => 32, 'height_from_cm' => firstOption('reference_data.height_list', 30), 'height_to_cm' => firstOption('reference_data.height_list', 50),
        'marital_status' => ['Unmarried'], 'religions' => ['Christian'], 'denomination' => [firstOption('reference_data.denomination_list')],
        'mother_tongues' => [firstOption('reference_data.language_list', 3)], 'education_levels' => [firstOption('reference_data.educational_qualifications_list', 5)],
        'occupations' => [firstOption('reference_data.occupation_category_list', 4)], 'working_countries' => ['India'],
        'native_districts' => ['Dakshina Kannada'], 'income_range' => [firstOption('reference_data.annual_income_list', 3)], 'about_partner' => 'Kind person',
    ]);

    $before = editSnapshot($p->fresh());

    $this->actingAs($this->staff, 'admin');
    Livewire::test(EditUser::class, ['record' => $p->fresh()->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $after = editSnapshot($p->fresh());
    $changed = [];
    foreach ($before as $table => $row) {
        foreach ($row as $col => $value) {
            $new = $after[$table][$col] ?? null;
            if ((string) json_encode($value) !== (string) json_encode($new)) {
                $changed[] = "{$table}.{$col}: " . json_encode($value) . ' → ' . json_encode($new);
            }
        }
    }

    expect($changed)->toBe([])
        // and the edit log has nothing to record
        ->and(App\Models\AdminActivityLog::where('action', 'member_profile_updated')->where('model_id', $p->id)->count())->toBe(0);
});
