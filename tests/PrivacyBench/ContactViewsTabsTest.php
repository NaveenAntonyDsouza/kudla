<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Profile Views → "Viewed My Contact" and "Contacts I Viewed" (Nov 2026, idea from
| Chavara's Activity page), from the contact_views rows the plan limits record.
| Each member sees only their own rows, and never a member they may not see.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
    $m = $this->b->m;
    DB::table('contact_views')->insert([
        ['viewer_profile_id' => $m['V']->id, 'target_profile_id' => $m['T']->id, 'created_at' => now()->subDay()],
        ['viewer_profile_id' => $m['VB']->id, 'target_profile_id' => $m['T']->id, 'created_at' => now()],   // VB: blocked
        ['viewer_profile_id' => $m['VA']->id, 'target_profile_id' => $m['W']->id, 'created_at' => now()],   // someone else's
    ]);
});

it('shows a member who opened their contact, and whose contacts they opened', function () {
    $m = $this->b->m;

    $ids = fn ($res) => collect($res->assertOk()->viewData('views')->items())->pluck('id')->all();

    expect($ids($this->actingAs($m['T']->user)->get('/views?tab=contact_viewed_by')))->toContain($m['V']->id)
        ->not->toContain($m['VA']->id)
        ->and($ids($this->actingAs($m['V']->user)->get('/views?tab=contacts_i_viewed')))->toBe([$m['T']->id])
        ->and($ids($this->actingAs($m['VA']->user)->get('/views?tab=contact_viewed_by')))->toBe([]);
});

it('never lists a member the viewer may not see, and scans clean for private details', function () {
    $m = $this->b->m;
    $res = $this->actingAs($m['T']->user)->get('/views?tab=contact_viewed_by');

    expect(collect($res->viewData('views')->items())->pluck('id'))->not->toContain($m['VB']->id)
        ->and($this->b->scan((string) $res->getContent(), $m['T'], 'contact views'))->toBe([]);
});

it('counts "Viewed My Contact" on the dashboard the same way as the list, and links to it', function () {
    $m = $this->b->m;
    $res = $this->actingAs($m['T']->user)->get('/dashboard')->assertOk();

    expect($res->viewData('interestStats')['contact_views'])->toBe(1)   // V; VB is blocked
        ->and((string) $res->getContent())->toContain('Viewed My Contact')->toContain('tab=contact_viewed_by');
});
