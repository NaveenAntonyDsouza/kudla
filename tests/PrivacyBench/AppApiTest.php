<?php

use App\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Every mobile-app answer a member can get, as each kind of member, scanned for other
| members' private details and for members the viewer has no reason to see.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

it('gives no member another member\'s private details, or members they have no reason to see, in any app answer', function () {
    $b = $this->b;
    $problems = [];
    $discover = collect(array_keys(config('discover', [])))
        ->flatMap(fn ($cat) => array_slice(DiscoverController::homeLinkCounts($cat), 0, 2))
        ->map(fn ($l) => '/api/v1' . parse_url($l['url'], PHP_URL_PATH))->unique()->values()->all();
    $lists = ['/api/v1/matches/my', '/api/v1/matches/mutual', '/api/v1/search/partner', '/api/v1/search/keyword', '/api/v1/shortlist', '/api/v1/views', '/api/v1/discover/'];
    $endpoints = ['/api/v1/dashboard', '/api/v1/matches/my', '/api/v1/matches/mutual', '/api/v1/search/partner', '/api/v1/search/keyword?q=Bench',
        '/api/v1/shortlist', '/api/v1/views', '/api/v1/chats', '/api/v1/chats/badges', '/api/v1/notifications', '/api/v1/photo-requests',
        '/api/v1/blocked', '/api/v1/ignored', '/api/v1/interests', '/api/v1/interests?tab=received', '/api/v1/interests?tab=sent',
        '/api/v1/discover', ...$discover];

    foreach (['T', 'TP', 'V', 'VA', 'VS', 'VB', 'C', 'W'] as $key) {
        $viewer = $b->m[$key];
        $urls = $endpoints;
        foreach ($b->m as $other) {
            if ($other->id !== $viewer->id) {
                array_push($urls, "/api/v1/profiles/{$other->matri_id}", "/api/v1/search/id/{$other->matri_id}", "/api/v1/matches/score/{$other->matri_id}");
            }
        }
        foreach ($b->interests as $interest) {
            array_push($urls, "/api/v1/interests/{$interest->id}", "/api/v1/interests/{$interest->id}/messages");
        }

        foreach ($urls as $url) {
            $res = $this->actingAs($viewer->user, 'sanctum')->getJson($url);
            if ($res->getStatusCode() >= 500) {
                $problems[] = "{$url} — {$key}: server error {$res->getStatusCode()}";
                continue;
            }
            $body = (string) $res->getContent();
            $isList = (bool) collect($lists)->first(fn ($p) => str_starts_with($url, $p));
            // app lists carry members as "matri_id" fields
            $forListing = $isList ? preg_replace('#"matri_id"\s*:\s*"([A-Z]{2,4}\d{5,})"#', '/profile/$1', $body) : $body;
            array_push($problems, ...$b->scan($body, $viewer, $url), ...$b->listingViolations($forListing, $viewer, $url, $isList));
        }
    }

    expect(array_values(array_unique($problems)))->toBe([]);
});

it('answers someone else\'s interest and its messages with an error, never with the messages', function () {
    $b = $this->b;
    foreach (['V', 'VS', 'VB', 'W'] as $key) {
        foreach (['VA_T', 'C_T'] as $interest) {
            foreach (['', '/messages'] as $suffix) {
                $res = $this->actingAs($b->m[$key]->user, 'sanctum')->getJson('/api/v1/interests/' . $b->interests[$interest]->id . $suffix);
                expect($res->getStatusCode())->toBeIn([403, 404], "{$key} {$interest}{$suffix}")
                    ->and($b->scan((string) $res->getContent(), $b->m[$key], "app interest {$interest}{$suffix}"))->toBe([]);
            }
        }
    }
});

it('gives the accepted contact the contact details in the app, so the scan really finds them', function () {
    $b = $this->b;
    $body = (string) $this->actingAs($b->m['VA']->user, 'sanctum')->getJson('/api/v1/profiles/' . $b->m['T']->matri_id)->getContent();

    // the app's contact section carries the login email and the phones
    expect($body)->toContain('cny01login@bench.test')
        ->and(preg_replace('/\D/', '', $body))->toContain(Bench::phone(1, 2))
        ->and($b->scan($body, $b->m['VA'], 'control'))->toBe([]);
});
