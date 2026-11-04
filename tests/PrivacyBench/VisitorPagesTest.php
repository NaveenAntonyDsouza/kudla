<?php

use App\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Pages and app answers for visitors who are not signed in: only members who set no
| limit on who sees them, and no private details at all.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('shows visitors no private details and only members who set no limit', function () {
    $b = $this->b;
    $discover = collect(array_keys(config('discover', [])))
        ->flatMap(fn ($cat) => DiscoverController::homeLinkCounts($cat))
        ->map(fn ($l) => parse_url($l['url'], PHP_URL_PATH))->unique()->values()->all();
    $problems = [];
    $linked = 0;

    $urls = ['/', '/discover', '/search/results', '/search/results?keyword=Bench', '/search/results?gender=female', '/sitemap.xml', ...$discover];
    foreach ($b->m as $member) {
        array_push($urls, "/profile/{$member->matri_id}", "/profile/{$member->matri_id}?tab=contact", "/profile/{$member->matri_id}/print");
    }
    foreach ($urls as $url) {
        $res = $this->get($url);
        if ($res->getStatusCode() >= 500) {
            $problems[] = "{$url} — visitor: server error {$res->getStatusCode()}";
            continue;
        }
        $body = (string) $res->getContent();
        $linked += preg_match_all('#/profile/[A-Z]{2,4}\d{5,}#', $body);
        array_push($problems, ...$b->scan($body, null, $url), ...$b->listingViolations($body, null, $url, true));
    }

    foreach (['/api/v1/discover', ...array_map(fn ($p) => '/api/v1' . $p, $discover)] as $url) {
        $res = $this->getJson($url);
        $body = (string) $res->getContent();
        $forListing = preg_replace('#"matri_id"\s*:\s*"([A-Z]{2,4}\d{5,})"#', '/profile/$1', $body);
        array_push($problems, ...$b->scan($body, null, $url), ...$b->listingViolations($forListing, null, $url, true));
    }

    expect($linked)->toBeGreaterThan(0) // the visitor pages did list members
        ->and(array_values(array_unique($problems)))->toBe([]);
});

it('sends visitors who open a profile to the login page', function () {
    $b = $this->b;
    foreach (['T', 'TH', 'TL'] as $key) {
        expect($this->get("/profile/{$b->m[$key]->matri_id}")->getStatusCode())->toBe(302);
    }
});
