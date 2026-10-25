<?php

use App\Http\Controllers\ProfileController;
use App\Models\FamilyDetail;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use App\Models\SiteSetting;
use App\Rules\OldEnoughToMarry;
use App\Support\MinimumAge;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/*
| Profile edit review (25 Oct 2026, owner OK'd): men must be 21 to register
| (India's legal age to marry; a site setting), locked fields look locked and
| say who can change them, members can correct height / marital status /
| physical status, gender shows capitalised, Priest / Nun only for Christian
| families, and the text boxes that should be lists are now dropdowns
| (state / district, parents' occupation, sibling counts, relationship).
*/

beforeEach(function () {
    Schema::dropIfExists('site_settings');
    Schema::create('site_settings', function (Blueprint $t) {
        $t->id();
        $t->string('key')->unique();
        $t->text('value')->nullable();
        $t->timestamps();
    });
    Cache::flush();
});

afterEach(function () {
    foreach (['site_settings', 'lifestyle_info', 'differently_abled_info'] as $table) {
        Schema::dropIfExists($table);
    }
});

function editProfile(array $attrs = [], ?string $religion = null, array $family = []): Profile
{
    $p = new Profile();
    $p->forceFill(array_merge(['id' => 9100, 'full_name' => 'Test Member', 'gender' => 'male', 'height' => '170 cm - 5 ft 07 inch', 'marital_status' => 'Unmarried'], $attrs));
    $p->setRelation('religiousInfo', $religion ? (new ReligiousInfo())->forceFill(['religion' => $religion]) : null);
    $p->setRelation('familyDetail', $family ? (new FamilyDetail())->forceFill($family) : null);
    $p->setRelation('lifestyleInfo', null);
    $p->setRelation('differentlyAbledInfo', null);

    return $p;
}

it('needs men to be 21 and women 18 by default, and the site can change it', function () {
    $dob = fn (int $years) => now()->subYears($years)->subDay()->format('Y-m-d');
    $passes = fn (string $gender, int $years) => Validator::make(['dob' => $dob($years)], ['dob' => [new OldEnoughToMarry($gender)]])->passes();

    expect($passes('male', 20))->toBeFalse()
        ->and($passes('male', 21))->toBeTrue()
        ->and($passes('female', 18))->toBeTrue()
        ->and($passes('female', 17))->toBeFalse()
        ->and(MinimumAge::message('male'))->toBe('Men must be at least 21 years old to register.')
        ->and(MinimumAge::latestBirthDate())->toBe(now()->subYears(18)->format('Y-m-d'));

    SiteSetting::setValue('min_age_male', '18');                   // e.g. a site for the USA
    expect($passes('male', 19))->toBeTrue()
        ->and(MinimumAge::message('male'))->toBe('You must be at least 18 years old to register.');

    SiteSetting::setValue('min_age_male', '5');                    // nonsense falls back to the default
    expect(MinimumAge::forGender('male'))->toBe(21);
});

it('checks the age in every place a member can be created', function () {
    foreach (['app/Http/Requests/RegisterStep1Request.php', 'app/Http/Requests/Api/V1/Auth/RegisterStep1Request.php'] as $file) {
        expect(file_get_contents(base_path($file)))->toContain("new \\App\\Rules\\OldEnoughToMarry(\$this->input('gender'))")->not->toContain('before:-18 years');
    }
    // every sign-up form: Day / Month / Year dropdowns, no phone date picker that opens on the limit date
    foreach (['auth/register-step1', 'pages/home/garland', 'pages/home/classic', 'pages/home'] as $view) {
        expect(file_get_contents(resource_path("views/{$view}.blade.php")))->toContain('<x-dob-select id=')->not->toContain('type="date"')->not->toContain('subYears(18)');
    }
    expect(file_get_contents(app_path('Services/BulkImportValidator.php')))->toContain('MinimumAge::forGender(')
        ->and(file_get_contents(app_path('Filament/Resources/UserResource.php')))->toContain('MinimumAge::latestBirthDate($get(\'gender\'))')
        ->and(file_get_contents(app_path('Filament/Pages/SiteSettings.php')))->toContain("Select::make('min_age_male')")->toContain("Select::make('min_age_female')");
});

it('shows locked fields as locked, with who to ask, and lets members change height, marital status and physical status', function () {
    SiteSetting::setValue('phone', '7760020186');
    $html = view('profile.sections.primary-edit', ['profile' => editProfile()])->render();

    expect($html)->toContain('value="Male" readonly')                             // capitalised
        ->toContain('Name, gender and date of birth can only be changed by our team.')
        ->toContain('+91 77600 20186')
        ->toContain('<select name="height" required>')
        ->toContain('<option value="170 cm - 5 ft 07 inch" selected>')
        ->toContain('<select name="marital_status" x-model="maritalStatus" required>')
        ->toContain('name="children_with_me"')
        ->toContain('<select name="physical_status" x-model="physicalStatus">')
        ->not->toContain('class="bg-gray-50"');

    // a height typed in an old format stays selected instead of being blanked on save
    expect(view('profile.sections.primary-edit', ['profile' => editProfile(['height' => '5.7'])])->render())
        ->toContain('<option value="5.7" selected>');
});

it('saves height, marital status, children and physical status', function () {
    Schema::create('lifestyle_info', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->json('languages_known')->nullable(), $t->timestamps()]);
    Schema::create('differently_abled_info', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('profile_id'), $t->string('category')->nullable(), $t->string('specify')->nullable(), $t->text('description')->nullable(), $t->timestamps()]);

    $profile = new class extends Profile {
        public array $saved = [];
        public function update(array $attributes = [], array $options = []) { $this->saved = $attributes; return true; }
    };
    $profile->forceFill(['id' => 9101]);

    $request = Request::create('/profile/primary', 'POST', [
        'height' => '165 cm - 5 ft 05 inch', 'marital_status' => 'Divorced', 'children_with_me' => '1', 'children_not_with_me' => '0',
        'physical_status' => 'Differently Abled', 'da_category' => 'Hearing Impaired', 'mother_tongue' => 'Tulu',
    ]);
    (fn () => $this->updatePrimary($request, $profile))->call(new ProfileController());

    expect($profile->saved)->toMatchArray(['height' => '165 cm - 5 ft 05 inch', 'marital_status' => 'Divorced', 'children_with_me' => 1, 'physical_status' => 'Differently Abled'])
        ->and(\App\Models\DifferentlyAbledInfo::where('profile_id', 9101)->value('category'))->toBe('Hearing Impaired');

    // back to Unmarried: children reset
    $request = Request::create('/profile/primary', 'POST', ['height' => '165 cm - 5 ft 05 inch', 'marital_status' => 'Unmarried', 'children_with_me' => '2', 'mother_tongue' => 'Tulu']);
    (fn () => $this->updatePrimary($request, $profile))->call(new ProfileController());
    expect($profile->saved['children_with_me'])->toBe(0);
});

it('shows Priest and Nun only for Christian families', function () {
    $render = fn (Profile $p) => view('profile.sections.family-edit', ['profile' => $p])->render();

    expect($render(editProfile([], 'Hindu')))->not->toContain('brothers_priest')->not->toContain('sisters_nun')
        ->and($render(editProfile([], 'Christian')))->toContain('name="brothers_priest"')->toContain('name="sisters_nun"')
        ->and($render(editProfile([], 'Hindu', ['brothers_priest' => 1])))->toContain('name="brothers_priest"'); // never hide a filled-in value

    $onboarding = file_get_contents(resource_path('views/onboarding/step1.blade.php'));
    expect($onboarding)->toContain('@if($showClergy)')->toContain("=== 'Christian'");
});

it('uses dropdowns instead of text boxes where there is a list', function () {
    $family = view('profile.sections.family-edit', ['profile' => editProfile([], 'Hindu', ['father_occupation' => 'Retired', 'mother_occupation' => 'Runs a tailoring shop', 'brothers_married' => 2])])->render();

    expect($family)
        ->toContain('<option value="Retired" selected>Retired</option>')                  // father: from the list
        ->toContain('<option value="__other__" selected>Other (type it)</option>')        // mother: typed earlier, kept as "Other"
        ->toContain('Runs a tailoring shop')
        ->toContain('<select name="brothers_married" id="brothers_married">')
        ->toContain('<option value="2" selected>2</option>')
        ->not->toContain('type="number"');

    $education = file_get_contents(resource_path('views/profile/sections/education-edit.blade.php'));
    $location = file_get_contents(resource_path('views/profile/sections/location-edit.blade.php'));
    $contact = file_get_contents(resource_path('views/profile/sections/contact-edit.blade.php'));
    expect($education)->toContain('<x-profile-location-fields kind="working"')->not->toContain('name="working_state"')
        ->and($location)->toContain('<x-profile-location-fields kind="native"')->not->toContain('name="native_district"')
        ->and($contact)->toContain('<x-select-or-other name="reference_relationship"');

    $cascade = view('components.profile-location-fields', ['kind' => 'native', 'country' => 'India', 'state' => 'Karnataka', 'district' => 'Udupi', 'place' => 'Kapu'])->render();
    expect($cascade)->toContain('name="native_country"')->toContain('name="native_state"')->toContain('name="native_district"')->toContain('name="native_place"')
        ->toContain('/api/cascade/districts?state=')
        ->and(file_get_contents(resource_path('views/components/location-cascade.blade.php')))->toContain("@props(['prefix', 'label', 'district' => true])"); // the search filter is untouched
});

it('asks for the date of birth as Day / Month / Year with nothing pre-filled', function () {
    $html = Blade::render('<form x-data="{ dob: \'\', gender: \'\' }"><x-dob-select id="t_dob" /></form>');
    $year = (int) now()->format('Y');

    expect($html)
        ->toContain('<option value="">Day</option>')
        ->toContain('<option value="">Month</option>')
        ->toContain('<option value="">Year</option>')
        ->toContain('<option value="09">Sep</option>')
        ->toContain('<option value="' . ($year - 18) . '">')                 // youngest year for women
        ->not->toContain('<option value="' . ($year - 17) . '">')
        ->toContain('<option value="' . ($year - 75) . '">')
        ->toContain('<input type="hidden" name="date_of_birth" :value="dob">')
        ->toContain('return g === \'male\' ? 21 : (g === \'female\' ? 18 : 18)')
        ->toContain('Men must be at least 21 years old to register.')
        ->toContain('$refs.year.setCustomValidity(')
        ->not->toContain(' selected');                                       // no default year / month

    SiteSetting::setValue('min_age_male', '18');                              // e.g. a USA site
    expect(Blade::render('<form x-data="{ dob: \'\', gender: \'\' }"><x-dob-select /></form>'))
        ->toContain('return g === \'male\' ? 18 : (g === \'female\' ? 18 : 18)');
});
