# Week 4 Acceptance — Phase 2a Exit Gate

**This checkpoint closes Phase 2a.** After this, we merge `phase-2-mobile` into `main`, deploy to production, and start Phase 2b (Flutter).

---

## Endpoint count

Run `php artisan route:list --path=api` and count. Should be ~80+ endpoints across:

- Auth (12): register 5 + OTP 4 + login 3 + forgot/reset 2 + me/logout/devices 4
- Profile (4): dashboard, me, show, update-section
- Onboarding (5)
- Photos (7): CRUD + privacy + requests 4
- Search/Discover/Match (12)
- Interests (10)
- Membership (6)
- Engagement (15+): notifications 4, shortlist 2, views 1, block 3, report 1, ignore 2, id-proof 3, success-stories 2, contact 1, static-pages 1
- Settings (7)
- Reference (1 parameterised)
- Site settings (1)
- Webhooks (1)
- Devices (2)

---

## Full flow tests

### 1. New user → full journey
- [ ] Register 5 steps
- [ ] Verify email
- [ ] Dashboard loads
- [ ] Complete 4 onboarding steps → profile_completion_pct ≥ 90
- [ ] Upload profile photo
- [ ] Search for matches
- [ ] Send interest to a match
- [ ] (Other account) Accept interest
- [ ] Both upgrade to premium via Razorpay (use test card `4111 1111 1111 1111`)
- [ ] Exchange chat messages via `/messages` + `/since/{id}` polling
- [ ] Logout

### 2. Push notifications
- [ ] Register FCM token via `POST /devices`
- [ ] Send test notification via tinker → appears on device
- [ ] Trigger `interest.received` → push arrives
- [ ] Toggle `push_interest=false` in settings → next interest doesn't push

### 3. Edge cases
- [ ] Same-gender view: 403 GENDER_MISMATCH
- [ ] Blocked view: 404
- [ ] Daily interest limit (free = 5): 6th send returns 429
- [ ] Invalid Razorpay signature: 400 PAYMENT_FAILED
- [ ] 100% coupon: skips Razorpay, activates directly

---

## Tests + tools

- [ ] `./vendor/bin/pest --parallel` — all green
- [ ] Pest coverage ≥ 60% on controllers
- [ ] `php artisan scribe:generate` — docs show ~10 groups, ~80 endpoints
- [ ] `/docs.openapi.yaml` validates at editor.swagger.io
- [ ] Bruno collection: `bru run docs/bruno/kudla-api-v1 --env local` passes
- [ ] k6 load test: p95 < 400ms on hot endpoints at 100 concurrent users

## Infrastructure

- [ ] Pre-deploy DB backup taken
- [ ] All migrations tested on a staging DB clone
- [ ] Firebase credentials in place on server (not in git)
- [ ] Razorpay webhook URL configured in Razorpay dashboard
- [ ] Queue worker cron configured:
  ```
  * * * * * cd /home/u562383594/domains/kudlamatrimony.com/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
  ```

## Deploy to production

When above ✅:

1. **Take full DB backup** via admin panel
2. **Merge** `phase-2-mobile` into `main`
3. **Run `deploy-build.ps1`** — zip + upload
4. **SSH to server, extract, run:**
   ```
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan scribe:generate
   ```
5. **Smoke test** a few curl calls against production
6. **Monitor** laravel.log + crash reports for 24h

---

## Go/No-Go for Phase 2b

- ✅ All above checks green → start [phase-2b-flutter/](../../phase-2b-flutter/README.md)
- 🛑 Anything red → fix first

**Phase 2a complete ✅ — API layer production-ready**
