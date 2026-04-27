<?php

use App\Http\Controllers\Api\V1\DiscoverController;
use App\Services\DiscoverConfigService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| DiscoverController — public category browsing
|--------------------------------------------------------------------------
| GET /discover · GET /discover/{category} · GET /discover/{category}/{slug}
|
| All 3 public. Hub cached 5 min (test suite clears the cache between
| tests to avoid cross-contamination). Tests stub executePaginated to
| avoid running queries against SQLite :memory: (which lacks the
| profiles + related tables).
|
| Reference: docs/mobile-app/reference/ui-safe-api-checklist.md
*/

/**
 * Build a DiscoverController with executePaginated stubbed so results
 * endpoints return a pre-built paginator without touching the DB.
 */
function buildDiscoverController(int $totalItems = 0, array $items = []): DiscoverController
{
    return new class(app(DiscoverConfigService::class), $totalItems, $items) extends DiscoverController {
        public static int $capturedPerPage = 0;

        public function __construct(
            DiscoverConfigService $discover,
            private int $totalForStub,
            private array $itemsForStub,
        ) {
            parent::__construct($discover);
        }

        protected function executePaginated(Builder $query, int $perPage): LengthAwarePaginator
        {
            self::$capturedPerPage = $perPage;

            return new LengthAwarePaginator(
                items: $this->itemsForStub,
                total: $this->totalForStub,
                perPage: $perPage,
                currentPage: 1,
                options: ['path' => '/api/v1/discover'],
            );
        }
    };
}

beforeEach(function () {
    // Hub is cached 5 min; clear between tests so each run sees fresh
    // config and test changes aren't masked by a stale cached payload.
    Cache::forget('api:v1:discover:hub');
});

/* ==================================================================
 |  GET /discover — hub
 | ================================================================== */

it('hub returns all 13 categories from config', function () {
    $response = buildDiscoverController()->hub();
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect(count($data))->toBe(13);  // per config/discover.php
});

it('hub response includes the discriminator flags + show_search', function () {
    $response = buildDiscoverController()->hub();
    $data = $response->getData(true)['data'];

    foreach ($data as $entry) {
        expect($entry)->toHaveKeys([
            'category', 'label', 'show_search',
            'has_subcategories', 'has_direct_filter',
        ]);
        expect($entry['show_search'])->toBeBool();
        expect($entry['has_subcategories'])->toBeBool();
        expect($entry['has_direct_filter'])->toBeBool();
    }
});

it('hub correctly flags nri-matrimony as subcategory-based', function () {
    $response = buildDiscoverController()->hub();
    $data = $response->getData(true)['data'];

    $nri = collect($data)->firstWhere('category', 'nri-matrimony');
    expect($nri)->not->toBeNull();
    expect($nri['has_subcategories'])->toBeTrue();
    expect($nri['has_direct_filter'])->toBeFalse();
});

it('hub correctly flags kannadiga-matrimony as direct_filter', function () {
    $response = buildDiscoverController()->hub();
    $data = $response->getData(true)['data'];

    $kanna = collect($data)->firstWhere('category', 'kannadiga-matrimony');
    expect($kanna)->not->toBeNull();
    expect($kanna['has_subcategories'])->toBeFalse();
    expect($kanna['has_direct_filter'])->toBeTrue();
});

it('hub returns an empty array when discover config is missing', function () {
    config(['discover' => null]);

    $response = buildDiscoverController()->hub();

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['data'])->toBe([]);
});

/* ==================================================================
 |  GET /discover/{category}
 | ================================================================== */

it('category returns 404 when the category key is unknown', function () {
    $response = buildDiscoverController()->category('nonexistent-category');

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['code'])->toBe('NOT_FOUND');
});

it('category returns subcategory list for subcategory-based categories', function () {
    // second-marriage uses a literal subcategories array — no DB, safe
    // to call the real resolution path.
    $response = buildDiscoverController()->category('second-marriage');
    $data = $response->getData(true)['data'];

    expect($response->getStatusCode())->toBe(200);
    expect($data)->toHaveKeys(['category', 'label', 'show_search', 'subcategories']);
    expect($data['category'])->toBe('second-marriage');
    expect(count($data['subcategories']))->toBe(4);
    expect($data['subcategories'][0])->toHaveKeys(['label', 'slug', 'filter']);
});

it('category returns paginated results for direct_filter categories', function () {
    $response = buildDiscoverController(totalItems: 42)->category('kannadiga-matrimony');
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body)->toHaveKeys(['data', 'meta']);
    expect($body['data'])->toBeArray();
    expect($body['meta'])->toHaveKeys(['page', 'per_page', 'total', 'last_page', 'category', 'label', 'direct_filter']);
    expect($body['meta']['category'])->toBe('kannadiga-matrimony');
    expect($body['meta']['direct_filter'])->toBe(['mother_tongue' => 'Kannada']);
    expect($body['meta']['total'])->toBe(42);
});

/* ==================================================================
 |  GET /discover/{category}/{slug}
 | ================================================================== */

it('results returns 404 when the category key is unknown', function () {
    $response = buildDiscoverController()->results('nonexistent', 'some-slug');

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['message'])->toBe('Category not found.');
});

it('results returns 404 when the slug does not match any subcategory', function () {
    $response = buildDiscoverController()->results('second-marriage', 'nonexistent-status');

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['error']['message'])->toBe('Subcategory not found.');
});

it('results returns paginated results with filter echoed in meta', function () {
    // second-marriage/annulled uses a literal subcategory with
    // filter=['marital_status' => 'Annulled'] — resolution is pure PHP,
    // the paginator is stubbed so no DB is touched.
    $response = buildDiscoverController(totalItems: 7)->results('second-marriage', 'annulled');
    $body = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($body['meta']['category'])->toBe('second-marriage');
    expect($body['meta']['slug'])->toBe('annulled');
    expect($body['meta']['filter'])->toBe(['marital_status' => 'Annulled']);
    expect($body['meta']['total'])->toBe(7);
});

it('results uses RESULTS_PER_PAGE as the page size', function () {
    $controller = buildDiscoverController();
    $controller->results('second-marriage', 'annulled');

    expect($controller::$capturedPerPage)->toBe(DiscoverController::RESULTS_PER_PAGE);
});

/* ==================================================================
 |  Constants (contract lock)
 | ================================================================== */

it('exposes RESULTS_PER_PAGE=20 and HUB_CACHE_TTL_SECONDS=300', function () {
    expect(DiscoverController::RESULTS_PER_PAGE)->toBe(20);
    expect(DiscoverController::HUB_CACHE_TTL_SECONDS)->toBe(300);
});
