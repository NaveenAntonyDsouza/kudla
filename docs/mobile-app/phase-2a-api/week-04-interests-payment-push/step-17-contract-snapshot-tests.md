# Step 17 — Contract Snapshot Tests

## Goal
Pin the exact response shape of every `/api/v1/*` endpoint. Any future
change that accidentally renames a field, changes a type, or drops a key
fails the build with a clear diff message.

This is the single most valuable artifact for Phase 2b. It's a tripwire
that prevents "Flutter silently broke because the API drifted" — the
#1 class of mobile-on-backend regression.

## Prerequisites
- [ ] Step 16 (Bruno collection) complete
- [ ] Pest v4 installed

## Procedure

### 1. Install Pest snapshot plugin

```bash
composer require --dev spatie/pest-plugin-snapshots
```

### 2. Create the snapshot test

`tests/Feature/Api/V1/ApiContractSnapshotTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

use function Spatie\Snapshots\assertMatchesJsonSnapshot;

/*
|--------------------------------------------------------------------------
| Contract Snapshot Tests
|--------------------------------------------------------------------------
| For every /api/v1/* endpoint we capture the SHAPE of the response —
| keys + types, not values. Diffs fail loudly if a future change drifts
| the shape.
|
| Update snapshots intentionally via:
|   ./vendor/bin/pest --filter=ApiContractSnapshot -d --update-snapshots
*/

beforeEach(function () {
    Cache::flush();
    // Seed cache-only data where relevant (to avoid MySQL test DB setup)
});

/**
 * Normalize a JSON response into a shape-only tree:
 *   strings -> "string"
 *   integers -> "integer"
 *   booleans -> "boolean"
 *   null     -> "null"
 *   arrays   -> recurse
 *   objects  -> recurse
 */
function shape(mixed $value): mixed
{
    if (is_string($value)) return 'string';
    if (is_int($value)) return 'integer';
    if (is_float($value)) return 'number';
    if (is_bool($value)) return 'boolean';
    if (is_null($value)) return 'null';
    if (is_array($value)) {
        // Distinguish list (sequential ints) from associative (object)
        if (array_is_list($value)) {
            return [count($value) > 0 ? shape($value[0]) : 'empty_array'];
        }
        return array_map(fn ($v) => shape($v), $value);
    }
    return 'unknown';
}

it('GET /api/v1/health matches contract', function () {
    $response = getJson('/api/v1/health');
    assertMatchesJsonSnapshot(shape($response->json()));
});

it('GET /api/v1/site/settings matches contract', function () {
    // pre-seed cache to avoid DB
    Cache::put('api:v1:site-settings', [
        'site' => ['name' => 'Test', 'logo_url' => null, /* full shape */],
        'theme' => [/* … */],
        'features' => [/* all booleans */],
        'registration' => [/* ints + string */],
        'membership' => ['razorpay_key' => '', 'currency' => 'INR'],
        'app' => [/* version strings */],
        'social_links' => [/* strings */],
        'policies' => [/* URLs */],
    ], now()->addMinutes(5));

    $response = getJson('/api/v1/site/settings');
    assertMatchesJsonSnapshot(shape($response->json()));
});

it('GET /api/v1/reference matches contract', function () {
    $response = getJson('/api/v1/reference');
    assertMatchesJsonSnapshot(shape($response->json()));
});

// … one test per endpoint (~82 total)
```

### 3. Structure tests by endpoint group

Split into files for readability:

```
tests/Feature/Api/V1/ContractSnapshots/
├── AuthContractTest.php         (register, OTP, login, password, me, logout)
├── ProfileContractTest.php      (dashboard, me, show, update)
├── PhotoContractTest.php        (list, upload, primary, delete, requests)
├── SearchContractTest.php       (partner, keyword, id, saved, discover, matches)
├── InterestContractTest.php     (send, accept, decline, reply, since)
├── MembershipContractTest.php   (plans, order, verify, webhook)
├── EngagementContractTest.php   (shortlist, views, block, report, ignore, id-proof)
├── NotificationContractTest.php (list, read, unread-count)
├── SettingsContractTest.php     (7 endpoints)
├── DeviceContractTest.php       (register, revoke)
└── ReferenceContractTest.php    (config, reference list)
```

### 4. Document "when to update a snapshot"

Add `docs/mobile-app/reference/contract-snapshot-workflow.md`:

```
Contract snapshots are the ONE place we commit to the exact response shape.

When a snapshot test fails:

  1. First question: is this a bug or an intended change?
     - If bug: FIX the controller so the response matches the snapshot.
     - If intended: update the snapshot.

  2. To update: run
       ./vendor/bin/pest --filter=ApiContractSnapshot -d --update-snapshots
     Review the new .snap files in the diff.
     Commit the changes WITH an explanation in the commit message of
     why the contract changed.

  3. Bump the API version if the change is BREAKING:
     - Field removed / renamed / type changed = breaking
     - Field added (as optional) = non-breaking
     - New endpoint = non-breaking
```

### 5. Run + commit

```bash
./vendor/bin/pest --filter=ApiContractSnapshot
# First run: all snapshots auto-created
./vendor/bin/pest --filter=ApiContractSnapshot
# Second run: all pass (stable)

git add tests/Feature/Api/V1/ContractSnapshots __snapshots__ composer.* \
        docs/mobile-app/reference/contract-snapshot-workflow.md
git commit -m "phase-2a wk-04: step-17 contract snapshot tests for all endpoints"
```

## Verification

- [ ] ~82 snapshot tests, all pass
- [ ] Deliberate change (e.g., rename a field) causes a snapshot test to fail with a clear diff
- [ ] Update workflow documented
- [ ] Workflow doc committed

## Next step
→ [step-18-scribe-audit.md](step-18-scribe-audit.md)
