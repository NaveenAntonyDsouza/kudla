<?php

use App\Http\Controllers\DiscoverController;
use App\Models\Profile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\PrivacyBench\Bench;

/*
| Every website page a member can open, as each kind of member, scanned for other
| members' private details and for members the viewer has no reason to see.
*/

beforeEach(function () {
    Mail::fake();
    Http::fake();
    $this->b = Bench::build();
});

/** Pages that list members (strict: only members the viewer may see). */
const BENCH_LIST_PAGES = ['/matches', '/matches/mutual', '/search/results', '/shortlist', '/views', '/discover/'];

function benchDiscoverPaths(): array
{
    return collect(array_keys(config('discover', [])))
        ->flatMap(fn ($cat) => array_slice(DiscoverController::homeLinkCounts($cat), 0, 2))
        ->map(fn ($l) => parse_url($l['url'], PHP_URL_PATH))->unique()->values()->all();
}

it('shows no member another member\'s private details, or members they have no reason to see, on any website page', function () {
    $b = $this->b;
    $problems = [];
    $pages = ['/dashboard', '/matches', '/matches/mutual', '/search/results', '/search/results?keyword=Bench', '/discover', '/shortlist',
        '/views?tab=viewed_by', '/views?tab=viewed', '/chats', '/notifications', '/photo-requests', '/blocked', '/ignored', '/saved-searches',
        '/interests', '/interests?tab=received', '/interests?tab=sent', '/interests?tab=starred', '/interests?tab=trash', ...benchDiscoverPaths()];

    foreach (['T', 'TP', 'V', 'VA', 'VS', 'VB', 'C', 'W'] as $key) {
        $viewer = $b->m[$key];
        $urls = $pages;
        foreach ($b->m as $other) {
            if ($other->id === $viewer->id) {
                continue;
            }
            foreach (['', '?tab=contact', '?tab=family', '?tab=partner'] as $tab) {
                $urls[] = "/profile/{$other->matri_id}{$tab}";
            }
            $urls[] = "/profile/{$other->matri_id}/print";
            $urls[] = "/report/{$other->matri_id}";
        }
        foreach ($b->interests as $interest) {
            $urls[] = "/interests/{$interest->id}";
        }

        foreach ($urls as $url) {
            $res = $this->actingAs($viewer->user)->get($url);
            $code = $res->getStatusCode();
            if ($code >= 500) {
                $problems[] = "{$url} — {$key}: server error {$code}";
                continue;
            }
            $body = (string) $res->getContent();
            $isList = (bool) collect(BENCH_LIST_PAGES)->first(fn ($p) => str_starts_with($url, $p));
            array_push($problems, ...$b->scan($body, $viewer, $url), ...$b->listingViolations($body, $viewer, $url, $isList));
        }
    }

    expect(array_values(array_unique($problems)))->toBe([]);
});

it('answers someone else\'s interest page with 403, never with its messages', function () {
    $b = $this->b;
    foreach (['V', 'VS', 'VB', 'W', 'TP'] as $key) {
        foreach (['VA_T', 'C_T'] as $interest) {
            $res = $this->actingAs($b->m[$key]->user)->get('/interests/' . $b->interests[$interest]->id);
            expect($res->getStatusCode())->toBeIn([403, 404], "{$key} opening {$interest}")
                ->and($b->scan((string) $res->getContent(), $b->m[$key], "interest {$interest}"))->toBe([]);
        }
    }
});

it('shows the contact details to the accepted contact, so the scan really finds them', function () {
    $b = $this->b;
    $body = (string) $this->actingAs($b->m['VA']->user)->get('/profile/' . $b->m['T']->matri_id . '?tab=contact')->getContent();

    // the control: T's phone is on the page for VA (accepted) and the scan allows it
    expect(preg_replace('/\D/', '', $body))->toContain(Bench::phone(1, 2))
        ->and($b->scan($body, $b->m['VA'], 'control'))->toBe([]);
});
