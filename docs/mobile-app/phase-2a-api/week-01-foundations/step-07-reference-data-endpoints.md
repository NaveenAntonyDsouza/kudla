# Step 7 — Reference Data Endpoints (`GET /api/v1/reference/{list}`)

## Goal
Expose the cascading dropdown data (religions, castes, states, occupations, etc.) as JSON endpoints. Flutter populates all dropdowns from these — same data the web already uses via `ReferenceDataService`.

## Prerequisites
- [ ] [step-06 — site settings endpoint](step-06-site-settings-endpoint.md) complete
- [ ] Familiarity with `App\Services\ReferenceDataService`

## Procedure

### 1. Audit what lists exist

Read `app/Services/ReferenceDataService.php` and `config/reference_data.php`. Note which lists support filters (cascading) vs simple lists.

From design doc: ~25 lists exist. Supported `list` path values:
- `religions`, `castes`, `sub-castes`, `denominations`, `dioceses`, `occupations`, `education-levels`, `mother-tongues`, `countries`, `states`, `districts`, `communities`, `income-ranges`, `complexion`, `body-type`, `marital-status`, `family-status`, `diet`, `drinking`, `smoking`, `physical-status`, `blood-group`, `manglik`, `rashi`, `nakshatra`, `residency-status`, `how-did-you-hear`, `created-by`

### 2. Create the controller

Create `app/Http/Controllers/Api/V1/ReferenceDataController.php`:

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Services\ReferenceDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReferenceDataController extends BaseApiController
{
    private const VALID_LISTS = [
        'religions', 'castes', 'sub-castes', 'denominations', 'dioceses',
        'occupations', 'education-levels', 'mother-tongues',
        'countries', 'states', 'districts', 'communities',
        'income-ranges', 'complexion', 'body-type',
        'marital-status', 'family-status', 'diet',
        'drinking', 'smoking', 'physical-status',
        'blood-group', 'manglik', 'rashi', 'nakshatra',
        'residency-status', 'how-did-you-hear', 'created-by',
    ];

    private const CASCADING_FILTERS = [
        'castes' => 'religion',
        'sub-castes' => 'caste',
        'denominations' => 'religion',
        'dioceses' => 'denomination',
        'states' => 'country',
        'districts' => 'state',
    ];

    /**
     * GET /api/v1/reference/{list}
     */
    public function show(Request $request, string $list, ReferenceDataService $refs): JsonResponse
    {
        if (! in_array($list, self::VALID_LISTS, true)) {
            return ApiResponse::error(
                code: 'NOT_FOUND',
                message: "Reference list '{$list}' does not exist.",
                status: 404,
            );
        }

        // Validate filter if list is cascading
        $filterKey = self::CASCADING_FILTERS[$list] ?? null;
        $filterValue = $filterKey ? $request->query($filterKey) : null;

        // Build cache key (include filter so different religions give different castes)
        $cacheKey = "api:v1:reference:{$list}" . ($filterValue ? ":{$filterValue}" : '');

        $data = Cache::remember($cacheKey, now()->addHour(), function () use ($list, $filterKey, $filterValue, $refs) {
            return $this->fetchList($list, $filterKey, $filterValue, $refs);
        });

        return ApiResponse::ok($data);
    }

    /**
     * Dispatch to the correct ReferenceDataService method.
     * Each method returns either a flat list of labels
     * or a list of {slug, label} pairs — we normalize here.
     *
     * @return array<int, array{slug: string, label: string}|string>
     */
    private function fetchList(string $list, ?string $filterKey, ?string $filterValue, ReferenceDataService $refs): array
    {
        return match ($list) {
            'religions' => $this->normalize($refs->get('religions')),
            'castes' => $this->normalize($refs->getCastes($filterValue)),
            'sub-castes' => $this->normalize($refs->getSubCastes($filterValue)),
            'denominations' => $this->normalize($refs->getDenominations($filterValue)),
            'dioceses' => $this->normalize($refs->getDioceses($filterValue)),
            'occupations' => $this->normalize($refs->get('occupations')),
            'education-levels' => $this->normalize($refs->get('education_levels')),
            'mother-tongues' => $this->normalize($refs->get('mother_tongues')),
            'countries' => $this->normalize($refs->get('countries')),
            'states' => $this->normalize($refs->getStates($filterValue)),
            'districts' => $this->normalize($refs->getDistricts($filterValue)),
            'communities' => $refs->getCommunities(),
            'income-ranges' => $this->normalize($refs->get('income_ranges')),
            'complexion' => $this->normalize($refs->get('complexion')),
            'body-type' => $this->normalize($refs->get('body_type')),
            'marital-status' => $this->normalize($refs->get('marital_status')),
            'family-status' => $this->normalize($refs->get('family_status')),
            'diet' => $this->normalize($refs->get('diet')),
            'drinking' => $this->normalize($refs->get('drinking')),
            'smoking' => $this->normalize($refs->get('smoking')),
            'physical-status' => $this->normalize($refs->get('physical_status')),
            'blood-group' => $this->normalize($refs->get('blood_group')),
            'manglik' => $this->normalize($refs->get('manglik')),
            'rashi' => $this->normalize($refs->get('rashi')),
            'nakshatra' => $this->normalize($refs->get('nakshatra')),
            'residency-status' => $this->normalize($refs->get('residency_status')),
            'how-did-you-hear' => $this->normalize($refs->get('how_did_you_hear')),
            'created-by' => $this->normalize($refs->get('created_by')),
            default => [],
        };
    }

    /**
     * Normalize list to either flat strings or {slug, label} objects.
     * The existing ReferenceDataService may return either shape; we preserve that.
     */
    private function normalize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        // If first element is already an object with slug/label, pass through
        if (isset($raw[0]) && is_array($raw[0]) && isset($raw[0]['slug'])) {
            return array_values($raw);
        }

        // If associative (slug => label), convert to objects
        if (! empty($raw) && ! array_is_list($raw)) {
            return array_values(array_map(
                fn ($label, $slug) => ['slug' => (string) $slug, 'label' => (string) $label],
                $raw,
                array_keys($raw),
            ));
        }

        // Flat list of strings — return as-is
        return array_values($raw);
    }
}
```

> **Note:** the exact `ReferenceDataService` method names may differ. Open the real file and adjust method calls to match.

### 3. Register route

In `routes/api.php`:

```php
Route::get('/reference/{list}', [
    \App\Http\Controllers\Api\V1\ReferenceDataController::class,
    'show',
])->where('list', '[a-z-]+');
```

### 4. Test with curl

```bash
# Simple flat list
curl -H "Accept: application/json" http://localhost:8000/api/v1/reference/religions | jq

# Cascading: castes for Hindu
curl -H "Accept: application/json" "http://localhost:8000/api/v1/reference/castes?religion=Hindu" | jq

# Unknown list
curl -H "Accept: application/json" http://localhost:8000/api/v1/reference/invalid | jq
```

Expected shape:
```json
{
  "success": true,
  "data": [
    { "slug": "hindu", "label": "Hindu" },
    { "slug": "christian", "label": "Christian" },
    ...
  ]
}
```

Or simple list:
```json
{ "success": true, "data": ["A+", "A-", "B+", ...] }
```

### 5. Write Pest tests

Create `tests/Feature/Api/V1/ReferenceDataTest.php`:

```php
<?php

use function Pest\Laravel\getJson;

beforeEach(function () {
    cache()->flush();
});

it('returns religions list', function () {
    $response = getJson('/api/v1/reference/religions');

    $response->assertOk()
        ->assertJsonStructure(['success', 'data']);

    expect($response->json('data'))->toBeArray()->not->toBeEmpty();
});

it('returns castes for a religion', function () {
    $response = getJson('/api/v1/reference/castes?religion=Hindu');

    $response->assertOk();
    expect($response->json('data'))->toBeArray();
});

it('returns 404 for unknown list', function () {
    $response = getJson('/api/v1/reference/nonexistent');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND'],
        ]);
});

it('caches responses', function () {
    $response1 = getJson('/api/v1/reference/religions');
    $response2 = getJson('/api/v1/reference/religions');

    $response1->assertOk();
    $response2->assertOk();
    expect($response1->json('data'))->toEqual($response2->json('data'));
});
```

### 6. Run tests

```bash
./vendor/bin/pest --filter=ReferenceData
```

## Verification

- [ ] 28 different `list` values return valid data (or empty array if the list isn't seeded)
- [ ] Cascading filters work (`castes?religion=Hindu` returns Hindu castes only)
- [ ] Unknown list returns 404 with envelope
- [ ] Cache hit is fast (< 10ms response time on second call)
- [ ] `php artisan cache:clear` followed by hitting endpoint repopulates cache

## Common issues

| Issue | Fix |
|-------|-----|
| `ReferenceDataService::get()` doesn't exist | Open `app/Services/ReferenceDataService.php` and map actual method names; adjust controller switch |
| Cascading filter ignored | Confirm query param is named correctly (e.g., `?religion=Hindu`, not `?category=Hindu`) |
| Data is associative in cache but client expects array | `Cache::remember` preserves the shape; if wrong shape returned, fix the `normalize()` helper |
| Response has `null` values | Filter out empty values in `normalize()`: `array_filter($raw, fn ($v) => !empty($v))` |

## Commit

```bash
git add app/Http/Controllers/Api/V1/ReferenceDataController.php routes/api.php tests/Feature/Api/V1/ReferenceDataTest.php
git commit -m "phase-2a wk-01: step-07 reference data endpoints with caching"
```

## Next step
→ [step-08-scribe-installation.md](step-08-scribe-installation.md)
