<?php

use App\Models\MembershipPlan;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| A paid-membership site (like Crasta): contact details need an accepted interest AND
| a plan that includes contacts. VA has the accepted interest but no plan; VS has a
| plan but only a pending interest; C has both.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
    SiteSetting::setValue('free_membership_enabled', '0');
    $plan = MembershipPlan::where('can_view_contact', true)->first() ?? MembershipPlan::first();
    $plan->forceFill(['can_view_contact' => true])->save();
    foreach (['VS', 'C'] as $key) {
        DB::table('user_memberships')->insert(['user_id' => $this->b->m[$key]->user_id, 'plan_id' => $plan->id, 'is_active' => true,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'created_at' => now(), 'updated_at' => now()]);
    }
});

it('opens contact details only with an accepted interest and a plan that includes them', function () {
    $b = $this->b;
    $problems = [];
    foreach (['VA', 'VS', 'C', 'V'] as $key) {
        $viewer = $b->m[$key];
        foreach (['', '?tab=contact', '/print'] as $suffix) {
            $url = "/profile/{$b->m['T']->matri_id}" . ($suffix === '/print' ? '/print' : $suffix);
            $problems = [...$problems, ...$b->scan((string) $this->actingAs($viewer->user)->get($url)->getContent(), $viewer, "paid mode {$url}")];
        }
        foreach (['/chats', '/dashboard', '/interests'] as $url) {
            $problems = [...$problems, ...$b->scan((string) $this->actingAs($viewer->user)->get($url)->getContent(), $viewer, "paid mode {$url}")];
        }
        $problems = [...$problems, ...$b->scan((string) $this->actingAs($viewer->user, 'sanctum')->getJson("/api/v1/profiles/{$b->m['T']->matri_id}")->getContent(), $viewer, 'paid mode app profile')];
    }

    // C (accepted + plan) really sees them, so the scan is not passing for want of data
    $cBody = (string) $this->actingAs($b->m['C']->user)->get("/profile/{$b->m['T']->matri_id}?tab=contact")->getContent();
    expect(preg_replace('/\D/', '', $cBody))->toContain(Bench::phone(1, 2))
        ->and(array_values(array_unique($problems)))->toBe([]);
});
