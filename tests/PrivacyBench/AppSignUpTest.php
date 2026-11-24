<?php

use App\Models\Profile;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| The app's 5 sign-up steps (24 Nov 2026). The API's sign-up service had drifted from the website's:
| step 2 failed with a server error for every app sign-up (it still read complexion / body_type, which
| moved to onboarding), and mother tongue, languages, the church and the working country were never
| saved. These walk the whole sign-up as the app does and check what was stored.
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    Cache::flush();
    SiteSetting::setValue('email_verification_enabled', '0');
    SiteSetting::setValue('phone_verification_enabled', '0');
});

afterEach(function () {
    SiteSetting::setValue('email_verification_enabled', '1');
});

function appSignUp(object $test): array
{
    $step1 = $test->postJson('/api/v1/auth/register/step-1', [
        'full_name' => 'App Tester', 'gender' => 'female', 'date_of_birth' => '1996-03-14',
        'phone' => '98'.random_int(10000000, 99999999), 'email' => 'app-'.uniqid().'@bench.test',
        'password' => 'app-pass-99', 'accept_terms' => true,
    ])->assertCreated()->assertJsonPath('data.next_step', 'register.step-2');

    return [$step1->json('data.token'), Profile::where('matri_id', $step1->json('data.profile.matri_id'))->firstOrFail()];
}

it('saves every sign-up step the app sends', function () {
    [$token, $profile] = appSignUp($this);
    $auth = ['Authorization' => 'Bearer '.$token];

    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-2', [
        'height' => '165 cm - 5 ft 05 inch', 'marital_status' => 'Never Married', 'mother_tongue' => 'English',
        'languages_known' => ['English', 'Italian'], 'religion' => 'Christian', 'denomination' => 'Roman Catholic',
        'caste' => 'Should be cleared', // not a Christian field
    ])->assertOk()->assertJsonPath('data.next_step', 'register.step-3');

    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-3', [
        'highest_education' => 'BSc Nursing', 'education_level' => 'Bachelors', 'occupation' => 'Nurse',
        'employment_category' => 'Private Sector', 'annual_income' => 'Not Specified',
    ])->assertOk()->assertJsonPath('data.next_step', 'register.step-4');

    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-4', [
        'native_country' => 'United Kingdom', 'native_place' => 'York', 'working_country' => 'United Kingdom',
        'working_city' => 'Leeds', 'mobile_number' => '+447700900987',
        'created_by' => 'Self', 'creator_name' => 'App Tester', 'creator_contact_number' => '+447700900987',
    ])->assertOk()->assertJsonPath('data.next_step', 'register.step-5');

    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-5', [
        'created_by' => 'Self', 'creator_name' => 'App Tester', 'creator_contact_number' => '+447700900987',
    ])->assertOk()->assertJsonPath('data.next_step', 'complete');

    $profile->refresh();
    expect($profile->height)->toBe('165 cm - 5 ft 05 inch')
        ->and($profile->marital_status)->toBe('Never Married')
        ->and($profile->mother_tongue)->toBe('English')
        ->and($profile->lifestyleInfo->languages_known)->toBe(['English', 'Italian'])
        ->and($profile->religiousInfo->religion)->toBe('Christian')
        ->and($profile->religiousInfo->denomination)->toBe('Roman Catholic')
        ->and($profile->religiousInfo->caste)->toBeNull()
        ->and($profile->educationDetail->occupation)->toBe('Nurse')
        ->and($profile->educationDetail->working_country)->toBe('United Kingdom')
        ->and($profile->educationDetail->working_city)->toBe('Leeds')
        ->and($profile->locationInfo->native_country)->toBe('United Kingdom')
        ->and($profile->locationInfo->native_place)->toBe('York')
        ->and($profile->contactInfo->primary_phone)->toBe('+447700900987')
        ->and($profile->created_by)->toBe('Self')
        ->and((int) $profile->onboarding_step_completed)->toBe(5)
        ->and((bool) $profile->onboarding_completed)->toBeTrue();

    // signed in again later (a new request), the app goes home
    auth()->forgetGuards();
    $this->withHeaders($auth)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.next_step', 'dashboard');
});

it('keeps the onboarding details when a member goes back to step 2 or step 4', function () {
    [$token, $profile] = appSignUp($this);
    $auth = ['Authorization' => 'Bearer '.$token];
    $profile->update(['complexion' => 'Fair', 'body_type' => 'Slim', 'onboarding_step_completed' => 4]);
    $profile->contactInfo()->updateOrCreate([], ['contact_person' => 'Anna', 'communication_address' => '1 High St']);

    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-2', [
        'height' => '160 cm', 'marital_status' => 'Never Married', 'mother_tongue' => 'English', 'religion' => 'Christian', 'denomination' => 'Roman Catholic',
    ])->assertOk();
    $this->withHeaders($auth)->postJson('/api/v1/auth/register/step-4', [
        'native_country' => 'Ireland', 'working_country' => 'Ireland', 'mobile_number' => '+353851234567',
        'created_by' => 'Self', 'creator_name' => 'App Tester', 'creator_contact_number' => '+353851234567',
    ])->assertOk();

    $profile->refresh();
    expect($profile->complexion)->toBe('Fair')
        ->and($profile->body_type)->toBe('Slim')
        ->and((int) $profile->onboarding_step_completed)->toBe(4)
        ->and($profile->contactInfo->contact_person)->toBe('Anna')
        ->and($profile->contactInfo->communication_address)->toBe('1 High St')
        ->and($profile->educationDetail->working_country)->toBe('Ireland');
});

it('counts each app limit on its own, so reading and polling never block sending or reporting', function () {
    [$token] = appSignUp($this);
    $auth = ['Authorization' => 'Bearer '.$token];

    // 35 badge refreshes (limit 60 a minute) ...
    for ($i = 0; $i < 35; $i++) {
        $this->withHeaders($auth)->getJson('/api/v1/notifications/unread-count')->assertOk();
    }
    // ... do not use up the 30-a-minute alerts limit or the 10-a-minute password limit
    $this->withHeaders($auth)->putJson('/api/v1/settings/alerts', ['push_interest' => false])->assertOk();
    $this->withHeaders($auth)->putJson('/api/v1/settings/password', [
        'current_password' => 'wrong-one', 'new_password' => 'new-pass-99', 'new_password_confirmation' => 'new-pass-99',
    ])->assertStatus(422);

    // each limit still holds on its own route
    for ($i = 0; $i < 9; $i++) {
        $this->withHeaders($auth)->putJson('/api/v1/settings/password', ['current_password' => 'wrong-one', 'new_password' => 'new-pass-99', 'new_password_confirmation' => 'new-pass-99']);
    }
    $this->withHeaders($auth)->putJson('/api/v1/settings/password', ['current_password' => 'wrong-one', 'new_password' => 'new-pass-99', 'new_password_confirmation' => 'new-pass-99'])
        ->assertStatus(429)->assertJsonPath('error.code', 'THROTTLED');
});

it('gives the app every choice list its sign-up and profile forms need', function () {
    $lists = $this->getJson('/api/v1/reference')->assertOk()->json('data.lists');
    foreach (['religions', 'marital-statuses', 'education-bands', 'employment-categories', 'created-by', 'complexions',
        'body-types', 'physical-statuses', 'family-statuses', 'heights', 'languages', 'denominations', 'education-levels',
        'occupations', 'income-ranges', 'countries', 'phone-codes'] as $slug) {
        expect($lists)->toContain($slug);
        $this->getJson('/api/v1/reference/'.$slug)->assertOk()->assertJsonPath('success', true);
    }
    expect($this->getJson('/api/v1/reference/marital-statuses')->json('data'))->toContain('Unmarried')
        ->and($this->getJson('/api/v1/reference/created-by')->json('data'))->toContain('Self / Candidate')
        ->and($this->getJson('/api/v1/reference/employment-categories')->json('data'))->toContain('Private');
});

it('stops push to a phone when it signs out, or when a password change signs it out', function () {
    [$token, $profile] = appSignUp($this);
    $user = $profile->user;
    $phoneA = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson('/api/v1/devices', ['fcm_token' => 'bench-a-'.uniqid(), 'platform' => 'android'])->assertCreated()->json('data.device_id');
    $tokenB = $user->createToken('second phone')->plainTextToken;
    auth()->forgetGuards();
    $phoneB = $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])->postJson('/api/v1/devices', ['fcm_token' => 'bench-b-'.uniqid(), 'platform' => 'android'])->assertCreated()->json('data.device_id');

    // phone B changes the password: phone A is signed out and gets no more pushes
    auth()->forgetGuards();
    $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])->putJson('/api/v1/settings/password', [
        'current_password' => 'app-pass-99', 'new_password' => 'app-pass-100', 'new_password_confirmation' => 'app-pass-100',
    ])->assertOk();
    expect((bool) \App\Models\Device::find($phoneA)->is_active)->toBeFalse()
        ->and((bool) \App\Models\Device::find($phoneB)->is_active)->toBeTrue();

    // phone B signs out: no more pushes there either
    auth()->forgetGuards();
    $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])->postJson('/api/v1/auth/logout')->assertOk();
    expect((bool) \App\Models\Device::find($phoneB)->is_active)->toBeFalse();
});

it('puts no premium badge on cards where membership is free', function () {
    [, $profile] = appSignUp($this);
    $badges = fn () => (new \App\Http\Resources\V1\ProfileCardResource($profile->fresh(['user.userMemberships'])))->resolve()['badges'];

    SiteSetting::setValue('free_membership_enabled', '1');
    try {
        expect($badges())->not->toContain('premium');
    } finally {
        SiteSetting::setValue('free_membership_enabled', '0');
    }
});
