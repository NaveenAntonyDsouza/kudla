# Phase 2a Acceptance Runbook

The static + sandbox-side validation is fully automated and clean (see commits `22ef4b1` → `7cd6c60` plus the comprehensive `9e63e94` / `ef05c46`). What remains needs the buyer's hands — a running production-grade environment, real third-party credentials, and a test device.

This runbook walks every manual check, with copy-pasteable commands, expected output, and acceptance criteria. Mark each box ✅ as you go. When all are green, Phase 2a is shippable to production.

---

## 0. Pre-flight

```
git checkout phase-2-mobile
composer install --no-dev --optimize-autoloader
cp .env.example .env       # only on a fresh checkout
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan scribe:generate
```

- [ ] `php artisan migrate:status` — every migration shows `Ran`
- [ ] `php tests/Tools/route-audit.php` — exit 0
- [ ] `php tests/Tools/auth-middleware-audit.php` — exit 0
- [ ] `php tests/Tools/openapi-validate.php` — exit 0
- [ ] `php tests/Tools/bruno-lint.php` — exit 0
- [ ] `php tests/Tools/mass-assignment-audit.php` — exit 0

---

## 1. Pest test suite

```
./vendor/bin/pest --colors=never
```

- [ ] Exit 0
- [ ] ≥ 660 tests pass (660+ after Cat 2 + Cat 4 additions)
- [ ] Optional: `./vendor/bin/pest --parallel` — should still be green; faster

If `pest --coverage` is wanted, install `pcov` (faster than xdebug for coverage):

```
pecl install pcov
echo "extension=pcov.so" >> $(php --ini | grep "Loaded Configuration File" | awk '{print $4}')
./vendor/bin/pest --coverage --min=60
```

Target: ≥ 60% line coverage on `app/Http/Controllers/Api/V1/*`.

---

## 2. Bruno collection live run

Bruno CLI runs the entire HTTP collection against a live server. Best done after a `migrate:fresh --seed` so the test env starts from a known-good DB state.

```
# 2a. Reset DB (DESTRUCTIVE — only on staging or local)
php artisan migrate:fresh --seed

# 2b. Start the dev server in another terminal
php artisan serve

# 2c. Install Bruno CLI (one-time)
npm install -g @usebruno/cli

# 2d. cd INTO the collection root (Bruno CLI v3+ requires this — the
#     command does not accept a path argument; you must be inside the
#     folder that contains bruno.json).
cd docs/bruno/kudla-api-v1
bru run --env local
```

- [ ] Exit 0
- [ ] All 36 .bru files pass (111 assertions)

Caveats:
- `auth.chain` runs first and seeds `{{token}}`, `{{matriId}}` env vars — every authenticated request after pulls from these.
- Throttle endpoints (`POST /contact` at 5/hr) may 429 if you re-run within an hour. Wait or `php artisan cache:clear`.
- `/static-pages/{slug}` returns 404 unless an admin has created an "about-us" page in Filament; the test allows both 200 and 404.

---

## 3. Razorpay test-mode end-to-end

Verifies the multi-gateway architecture against a real (test-mode) payment provider.

### 3a. Set up test credentials

In the Razorpay dashboard ([test mode](https://dashboard.razorpay.com/app/keys)), generate a Key Pair. Add to `.env`:

```
RAZORPAY_KEY_ID=rzp_test_...
RAZORPAY_KEY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...    # optional, only if testing webhook
```

Restart `php artisan serve` to pick up the env change.

### 3b. End-to-end flow

```
# Register a new user
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Test User",
    "gender": "male",
    "date_of_birth": "1995-04-12",
    "phone": "9876543210",
    "email": "rzp-smoke@example.com",
    "password": "rzp-smoke-pwd"
  }' | jq -r '.data.token')

# Pick a paid plan id (the seed should have one)
PLAN_ID=$(curl -s http://127.0.0.1:8000/api/v1/membership/plans \
  | jq '.data[] | select(.price_inr > 0) | .id' | head -1)

# Create the order
ORDER=$(curl -s -X POST "http://127.0.0.1:8000/api/v1/payment/razorpay/order" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"plan_id\": $PLAN_ID}")
echo "$ORDER" | jq .
```

- [ ] Response carries `subscription_id`, `gateway: "razorpay"`, `gateway_data.order_id` starting with `order_`
- [ ] Razorpay test dashboard shows the order under Test Mode → Orders

### 3c. Test card simulation

In a real client, you'd hand the `gateway_data` to the Razorpay JS SDK; for smoke purposes, complete a payment via the Razorpay dashboard's "test payment" tool, then call:

```
curl -s -X POST "http://127.0.0.1:8000/api/v1/payment/razorpay/verify" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subscription_id": SUBSCRIPTION_ID,
    "razorpay_order_id": "order_...",
    "razorpay_payment_id": "pay_...",
    "razorpay_signature": "..."
  }' | jq .
```

- [ ] Response is 200 with `is_active: true`
- [ ] DB: `subscriptions` row has `payment_status = paid`
- [ ] DB: `user_memberships` row exists for the user

### 3d. 100% coupon shortcut

```
# Admin: create a 100% discount coupon (Filament → Coupons)
# Then:
curl -s -X POST "http://127.0.0.1:8000/api/v1/payment/razorpay/order" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"plan_id\": $PLAN_ID, \"coupon_code\": \"FULLFREE\"}" \
  | jq .
```

- [ ] Response carries `gateway: "coupon"`, `amount_inr: 0`, `activated_via: "full_discount_coupon"`
- [ ] No order created in Razorpay dashboard (gateway was bypassed)
- [ ] Membership active immediately (no `/verify` call needed)

### 3e. Bad signature

```
curl -s -X POST "http://127.0.0.1:8000/api/v1/payment/razorpay/verify" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"subscription_id": SID, "razorpay_order_id":"x", "razorpay_payment_id":"y", "razorpay_signature":"deliberately-wrong"}'
```

- [ ] Response is 422 with `error.code = SIGNATURE_INVALID`

---

## 4. FCM push notification smoke

### 4a. Set up Firebase

1. Create a project in Firebase Console (or use existing).
2. Settings → Service Accounts → Generate New Private Key → download JSON.
3. Save to `storage/app/firebase-credentials.json` (already in `.gitignore`).

### 4b. Register a test device

```
DEVICE=$(curl -s -X POST http://127.0.0.1:8000/api/v1/devices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fcm_token": "REAL_FCM_TOKEN_FROM_FLUTTER_APP",
    "platform": "android",
    "app_version": "1.0.0"
  }')
echo "$DEVICE" | jq .
```

- [ ] 201 response with `device_id`
- [ ] DB: `devices` row exists

### 4c. Trigger an interest, observe push

From a different account, send an interest to the test user:

```
curl -s -X POST "http://127.0.0.1:8000/api/v1/profiles/{TARGET_MATRI_ID}/interest" \
  -H "Authorization: Bearer $OTHER_TOKEN" \
  -H "Content-Type: application/json" -d '{}'
```

- [ ] Push notification arrives on the test device within ~5 seconds
- [ ] Notification body contains the sender's name
- [ ] Tapping the notification deep-links into the interest screen (Phase 2b client work)

### 4d. Toggle settings.push_interest off

```
curl -s -X PUT http://127.0.0.1:8000/api/v1/settings/alerts \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"push_interest": false}'

# Send another interest from the other account
# ... (same as 4c)
```

- [ ] No push fires this time (settings flag respected)

---

## 5. Load test (k6)

Verifies p95 < 400 ms on hot endpoints under 100 concurrent users for 5 minutes — the acceptance gate.

### 5a. Install k6

```
# macOS
brew install k6
# Linux
sudo apt install k6
# Windows
choco install k6
```

### 5b. Capture a fresh test token

```
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" \
  -d '{ ... see §3b ... }' | jq -r '.data.token')
echo "$TOKEN"  # paste into BEARER_TOKEN below
```

### 5c. Run the load script

A baseline k6 script is provided at `tests/load/search-100rps.js` (TODO — buyer to author or I can scaffold on request). Minimal version:

```javascript
// tests/load/search-100rps.js
import http from 'k6/http';
import { check } from 'k6';
export const options = {
  vus: 100, duration: '5m',
  thresholds: { http_req_duration: ['p(95)<400'] },
};
const TOKEN = __ENV.BEARER_TOKEN;
export default function () {
  const res = http.get('http://127.0.0.1:8000/api/v1/search/partner?per_page=20', {
    headers: { Authorization: `Bearer ${TOKEN}` },
  });
  check(res, { 'status 200': (r) => r.status === 200 });
}
```

```
BEARER_TOKEN="$TOKEN" k6 run tests/load/search-100rps.js
```

- [ ] `http_req_duration p(95)` < 400ms
- [ ] Zero `http_req_failed` errors
- [ ] Server stays responsive throughout (no `connection refused`)

If p95 spikes, run with the query log enabled and look for N+1 patterns:

```
DB_LOG_QUERIES=true php artisan serve
# In another terminal
curl http://127.0.0.1:8000/api/v1/search/partner -H "Authorization: Bearer $TOKEN"
# Then check storage/logs/laravel.log for query count
```

A single search request should fire ≤ 5 queries (1 select + relations).

---

## 6. Production deploy

When all above is green:

### 6a. Backup

- [ ] Take full DB backup (admin panel → Settings → Database → Backup, or `mysqldump`)
- [ ] Snapshot `storage/app/private/` (id-proofs, success-story photos)

### 6b. Deploy

```
# On dev box
./deploy-build.ps1   # or whatever the project uses
# zip + scp/rsync to server

# On server
ssh user@server
cd /home/.../public_html
unzip -o build.zip
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan scribe:generate
php artisan storage:link   # if missing
```

- [ ] No migration errors
- [ ] `php artisan route:list --path=api/v1` shows 96 routes
- [ ] `curl https://kudlamatrimony.com/api/v1/health` → `{success:true, data:{status:ok, version:v1}}`

### 6c. Webhook configuration

- [ ] Razorpay dashboard → Webhooks → add `https://kudlamatrimony.com/api/v1/webhooks/razorpay` with `payment.captured`, `payment.failed`, `refund.processed` events selected
- [ ] Stripe / PayPal / Paytm / PhonePe — same pattern with their respective slugs

### 6d. Queue worker cron

```
# crontab -e
* * * * * cd /home/.../public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

- [ ] Cron line in place
- [ ] `tail /var/log/syslog` shows the worker firing every minute

### 6e. 24h monitoring

- [ ] Watch `storage/logs/laravel.log` — no SERVER_ERROR entries
- [ ] Crash reports (Crashlytics or equivalent) — no spike
- [ ] Razorpay dashboard — no `signature.invalid` events
- [ ] Push delivery rate (Firebase Console → Cloud Messaging) — > 90%

---

## Go / no-go for Phase 2b

When every box above is ✅, merge `phase-2-mobile` into `main` and tag a release:

```
git checkout main
git merge phase-2-mobile
git tag -a phase-2a-complete -m "Phase 2a — REST API layer production-ready"
git push origin main --tags
```

Then start [Phase 2b — Flutter](../phase-2b-flutter/README.md).

---

## What's automatically validated (no buyer action required)

These run as part of CI — no action needed unless they fail:

| Tool | What it pins | Run on CI |
|------|--------------|-----------|
| `tests/Tools/route-audit.php` | catalogue ↔ routes alignment | every PR |
| `tests/Tools/auth-middleware-audit.php` | @authenticated docblocks ↔ auth:sanctum middleware | every PR |
| `tests/Tools/openapi-validate.php` | every operation has summary + responses + auth | every PR |
| `tests/Tools/bruno-lint.php` | every .bru file has meta + tests + valid env vars | every PR |
| `tests/Tools/mass-assignment-audit.php` | every Eloquent model declares $fillable or $guarded | every PR |
| `vendor/bin/pest tests/Feature/Api/V1` | 660+ controller / resource / service tests | every PR |
| `php artisan scribe:generate` | OpenAPI 3.x spec stays in sync with controllers | every PR (no warnings) |

If you're forking a CI workflow, run all 5 lint scripts + the Pest suite before merge.
