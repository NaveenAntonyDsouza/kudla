<?php

use App\Models\ProfileView;
use App\Models\SiteSetting;
use App\Services\MemberCreationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| 6 Nov 2026, from a member's screenshots (KM100375): "Recent Profile Views" showed the
| same member twice (one view per day is recorded, so a member who looked on two days
| was listed twice), and another member's profile had the own-profile breadcrumb
| "My Home / View & Edit / Profile Preview".
*/

beforeEach(function () {
    Bench::guard();
    Mail::fake();
    Http::fake();
    SiteSetting::setValue('free_membership_enabled', '1'); // like Kudla: everyone sees who viewed them
    $make = fn (string $name, string $gender, string $phone) => app(MemberCreationService::class)->create(['full_name' => $name, 'gender' => $gender,
        'date_of_birth' => '1994-04-04', 'email' => strtolower(str_replace(' ', '-', $name)) . '@bench.test', 'phone' => $phone,
        'religion' => 'Hindu', 'caste' => 'Billava'], ['temp_password' => 'views-pass-' . substr($phone, -4)])['profile'];
    $this->me = $make('Viewed Bride', 'female', '9812600001');
    $this->twice = $make('Twice Groom', 'male', '9812600002');
    $this->once = $make('Once Groom', 'male', '9812600003');
    foreach ([$this->me, $this->twice, $this->once] as $p) {
        $p->forceFill(['onboarding_completed' => true])->save();
    }
    foreach ([[$this->twice, 3], [$this->twice, 1], [$this->once, 2]] as [$viewer, $daysAgo]) {
        ProfileView::create(['viewer_profile_id' => $viewer->id, 'viewed_profile_id' => $this->me->id, 'viewed_at' => now()->subDays($daysAgo)]);
    }
});

it('lists each viewer once on the dashboard, the viewers page and in the app', function () {
    $this->actingAs($this->me->user);
    $count = fn (string $html, string $matriId) => substr_count(strip_tags($html), $matriId);

    // the "Recent Profile Views" section only (other sections may list the same members)
    $html = (string) $this->get('/dashboard')->assertOk()->getContent();
    $start = (int) strpos($html, 'Recent Profile Views');
    $end = strpos($html, '<h2', $start + 30);
    $dashboard = strip_tags(substr($html, $start, $end === false ? 6000 : $end - $start));
    $page = (string) $this->get('/views')->assertOk()->getContent();
    expect($count($dashboard, $this->twice->matri_id))->toBe(1)
        ->and($count($dashboard, $this->once->matri_id))->toBe(1)
        ->and($count($page, $this->twice->matri_id))->toBe(1)
        ->and($count($page, $this->once->matri_id))->toBe(1);

    $app = $this->actingAs($this->me->user, 'sanctum')->getJson('/api/v1/views')->assertOk()->json();
    $ids = collect(data_get($app, 'data.viewers', []))->pluck('matri_id')->filter()->values()->all();
    expect(data_get($app, 'data.is_premium'))->toBeTrue()
        ->and(array_count_values($ids)[$this->twice->matri_id] ?? 0)->toBe(1)
        ->and(array_count_values($ids)[$this->once->matri_id] ?? 0)->toBe(1);
});

it('names the other member\'s profile by Matri ID, and keeps "Profile Preview" for one\'s own', function () {
    $this->actingAs($this->twice->user);
    $other = strip_tags((string) $this->get('/profile/' . $this->me->matri_id)->assertOk()->getContent());
    expect($other)->toContain('Profile ' . $this->me->matri_id)
        ->not->toContain('Profile Preview');

    $own = strip_tags((string) $this->get('/profile/preview')->assertOk()->getContent());
    expect($own)->toContain('Profile Preview');
});
