# Kudla API v1 — Bruno Collection

HTTP-level smoke tests for `/api/v1/*`. Bruno reads flat `.bru` files
that live in git — no cloud sync, no proprietary blobs.

## Quick start

```bash
# 1. Install Bruno CLI (one-time)
npm install -g @usebruno/cli

# 2. Boot the Laravel API
php artisan serve --port=8000

# 3. Run the whole collection
cd docs/bruno/kudla-api-v1
bru run --env local
```

Exit code `0` = all green. Non-zero = a drifted endpoint — check the
test names in the failure output for the specific assertion that broke.

## What's covered

This is a **scaffold-grade** collection (~35 `.bru` files) covering:

- **Full auth chain** — every test that needs auth picks up the bearer
  token captured by `01-auth/04-login-password.bru`
- **2-3 representative endpoints per resource group** — proves the
  pattern; the remaining ~50 endpoints follow the same shape and can
  be added by copy-paste.

The 11 groups under the collection root each have their own folder, and
files inside are sequenced (`seq:` in the meta block) so `bru run`
executes them in dependency order — auth before everything that uses
the token, list endpoints before any per-id mutations they discover.

## Group layout

| Folder | Endpoint count (current) | Total in API |
|--------|--------------------------|---------------|
| `01-auth` | 6 | 14 |
| `02-profiles` | 3 | 11 |
| `03-photos` | 1 | 9 |
| `04-search-discover` | 3 | 13 |
| `05-interests` | 2 | 11 |
| `06-membership` | 3 | 7 |
| `07-notifications` | 3 | 4 |
| `08-engagement` | 6 | 15 |
| `09-settings` | 4 | 7 |
| `10-devices` | 1 | 2 |
| `11-reference` | 4 | 4 |
| **Total** | **~36** | **~97** |

The remaining ~60 endpoints are deliberate scope — extend by following
the [Adding new endpoints](#adding-new-endpoints) pattern below.

## Environment variables

`environments/{local,staging,prod}.bru` carry:

| Var | Source | Used by |
|-----|--------|---------|
| `baseUrl` | hard-coded per env | every request |
| `token` | captured by login `.bru` via `vars:post-response` | every auth-required request |
| `matriId` | captured from register-step-1 response | profile + interest + shortlist + block tests |
| `interestId` | captured from interest send | accept/decline/star/trash tests |
| `notificationId` | captured from notifications list | mark-read tests |
| `deviceId` | captured from device register | revoke test |
| `savedSearchId` | captured from saved-search create | delete test |
| `idProofId` | captured from id-proof upload | destroy test |

## Adding new endpoints

Each `.bru` file follows this template:

```
meta {
  name: Human-readable name (shows in Bruno UI + CLI output)
  type: http
  seq: <next-int-in-folder>
}

<verb> {
  url: {{baseUrl}}/api/v1/<path>
  body: json | form-urlencoded | none
  auth: bearer | none
}

# ONLY if auth: bearer
auth:bearer {
  token: {{token}}
}

# ONLY for POST/PUT with a body
body:json {
  {
    "key": "value"
  }
}

# Capture response data into env vars for later requests
vars:post-response {
  myVar: res.body.data.some_field
}

tests {
  test("status is 200", () => {
    expect(res.status).to.equal(200);
  });
  test("envelope success", () => {
    expect(res.body.success).to.equal(true);
  });
  // ...endpoint-specific shape assertions...
}
```

Auth-required endpoints depend on the token captured by
`01-auth/04-login-password.bru`. If you re-order the collection, make
sure the login runs first.

## Useful flags

```bash
# Run a single folder
bru run --env local 01-auth

# Run a single file
bru run --env local 01-auth/05-me.bru

# Verbose (shows request/response per test)
bru run --env local --reporter-html out.html

# Set a one-off env var
bru run --env local --env-var token=existingToken123
```

## Caveats

- **Test data persistence**: endpoints that mutate state (register a
  user, upload a photo, send an interest) leave rows in your DB. Run
  `php artisan migrate:fresh --seed` between full-collection runs.
- **Premium-gated endpoints** assume the seeded test user is a free
  member. The premium-only branch (e.g. `/views?tab=viewed_by` viewer
  list) just asserts the empty-array shape; promote the user to test
  the populated branch.
- **Payment webhook endpoints** are NOT exercised here — they require
  signed payloads from the gateway servers themselves. See the per-
  gateway tests in `tests/Feature/Api/V1/{Razorpay,Stripe,PayPal,Paytm,PhonePe}WebhookTest.php`.
- **Rate limiting** kicks in if you re-run the same `.bru` rapidly
  (some endpoints are `5/hour`). Wait or `php artisan cache:clear` to
  reset throttle counters in dev.

## Reference

- Endpoint catalogue: [`docs/mobile-app/reference/endpoint-catalogue.md`](../../mobile-app/reference/endpoint-catalogue.md)
- Step-16 design doc: [`docs/mobile-app/phase-2a-api/week-04-interests-payment-push/step-16-bruno-collection.md`](../../mobile-app/phase-2a-api/week-04-interests-payment-push/step-16-bruno-collection.md)
- Bruno docs: https://docs.usebruno.com
