# 16. Implementation Summary

The end-to-end plan: build order, dependencies between tasks, milestones, test plan, risks, go/no-go gates, deliverables.

---

## 16.1 Phase Breakdown

### Phase 2a — API Layer (4 weeks)

Backend-only. No Flutter work yet.

| Week | Deliverables | Docs |
|------|--------------|------|
| 1 | Foundations installed (Sanctum, api.php, envelope, ApiResponse, ApiExceptionHandler). `/api/v1/site/settings` + `/reference/*` live. Pest test pattern established. | [01](01-api-foundations.md), [09 §9.9](09-engagement-api.md) |
| 2 | Auth + Registration + Onboarding endpoints complete. `AuthService`, `RegistrationService`, `OnboardingService` extracted. OTP channel column migration. Full register→dashboard loop via curl works. | [02](02-auth-api.md), [03](03-onboarding-api.md) |
| 3 | Profile + Photos + Search + Discover + Match endpoints complete. Access gates tested. Reference data cached. | [04](04-profile-api.md), [05](05-photo-api.md), [06](06-search-discover-api.md) |
| 4 | Interests + Chat polling + Membership + Razorpay + Coupons + Webhook + all engagement endpoints (notifications, shortlist, views, block, report, ignore, id-proof, settings, static pages). Push infra (FCM setup + device registration + notification dispatch). | [07](07-interests-chat-api.md), [08](08-membership-payment-api.md), [09](09-engagement-api.md), [10](10-push-notifications.md) |

**Phase 2a exit criteria:**
- Every endpoint in `/api/v1/*` returns correct envelope
- Pest integration test suite passes (target: 100+ tests)
- Full end-to-end curl flow: register → OTP → login → onboard → send interest → receive push → accept → chat → upgrade → pay → verify
- Production deploy of API layer (coexists with web routes, no web regression)
- Rate limits + security pass (OWASP basic check)
- Load test: `/api/v1/search` and `/api/v1/dashboard` handle 100 req/s without degradation

---

### Phase 2b — Flutter MVP (12 weeks)

| Week | Deliverables | Docs |
|------|--------------|------|
| 1 | Project scaffold, Firebase setup, Dio + Riverpod + GoRouter + secure storage wired. Splash + site config fetch. Deep link handling. | [11](11-flutter-foundations.md) |
| 2 | Login (all 3 tabs) + forgot password + reset password + device registration. Biometric enrolment. Foundations tested. | [12 §12.3, 12.11, 12.12, 12.13](12-flutter-auth-onboarding.md) |
| 3 | Registration step 1–5 + email/phone verification + cascading dropdowns + jathakam upload. | [12 §12.4–12.10](12-flutter-auth-onboarding.md) |
| 4 | Onboarding 4 optional steps + dashboard. | [03 API](03-onboarding-api.md), [13 §13.1](13-flutter-core-screens.md) |
| 5 | Search + filters + saved searches + discover hub/category/results. | [13 §13.2–13.5](13-flutter-core-screens.md) |
| 6 | Profile view (other) + My Profile (view + 9 section editors). | [13 §13.6, 13.7](13-flutter-core-screens.md) |
| 7 | Photo manager (upload, crop, privacy) + photo requests. | [13 §13.8](13-flutter-core-screens.md) |
| 8 | Interests inbox + chat thread (polling). | [13 §13.9, 13.10](13-flutter-core-screens.md) |
| 9 | Notifications + matches + shortlist + views + blocked + ignored. | [13 §13.11–13.13](13-flutter-core-screens.md) |
| 10 | Membership + Razorpay + checkout + payment history + receipt PDF. | [14 §14.1–14.4](14-flutter-membership-settings.md) |
| 11 | Settings (all sub-screens) + ID proof + biometric toggle + delete account. | [14 §14.5–14.13](14-flutter-membership-settings.md) |
| 12 | Polish — app shell, bottom nav, pull-to-refresh, offline cache, share card, shimmer, accessibility. Release build + ProGuard + signing. Internal testing. | [15](15-flutter-polish-launch.md) |

**Phase 2b exit criteria:**
- All screens implemented and tested against screenshots you provide
- Full user journey works on physical Android device (not just emulator)
- Release AAB builds without errors
- Manual testing checklist (see §16.7) passes
- Crashlytics initialized + Firebase Analytics (if enabled)

---

### Phase 2c — Launch & Iterate (4 weeks)

| Week | Deliverables |
|------|--------------|
| 1 | Internal testing track (Play Console), 10 internal testers. Daily crash + feedback review. |
| 2 | Closed testing track, 50 beta testers from user base. Fix 5–15 rough edges. |
| 3 | Production 10% rollout for 3 days. Monitor crash-free rate + reviews. |
| 4 | Production 50% → 100% rollout. Announcement to existing webview users via in-app + email. |

**Phase 2c exit criteria:**
- Crash-free session rate ≥ 99%
- Play Store rating ≥ 4.2 (from at least 30 new reviews)
- Existing webview-app users migrated to native (or still on webview but no regression)
- Zero critical unresolved bugs

---

## 16.2 Dependencies Map

```
                       ┌────────────────────────────────────────────┐
                       │  Phase 2a — API Layer                      │
                       ├────────────────────────────────────────────┤
        [01 foundations] → [02 auth] → [03 onboarding] → [04 profile]
                                                           ↓
                       [10 push] ← [09 engagement] ← [08 payment]
                            ↑          ↑              ↑
                            │          │              │
                          [07 interests] ← [06 search] ← [05 photo]
                       └──────────────────────┬─────────────────────┘
                                              ↓
                       ┌────────────────────────────────────────────┐
                       │  Phase 2b — Flutter MVP                    │
                       ├────────────────────────────────────────────┤
       [11 foundations] → [12 auth/onboarding] → [13 core screens]
                                                    ↓
                                   [14 membership/settings] → [15 polish/launch]
                       └──────────────────────┬─────────────────────┘
                                              ↓
                       ┌────────────────────────────────────────────┐
                       │  Phase 2c — Launch                         │
                       ├────────────────────────────────────────────┤
                              Internal → Closed → Prod rollout
                       └────────────────────────────────────────────┘
```

**Critical path:** API auth endpoints (week 2) must ship before Flutter auth (Flutter week 2).

**Parallelisable:**
- Flutter project setup (week 1) CAN start while API auth/onboarding is being built (API weeks 1–2) — they don't conflict
- In practice: do API weeks 1–4 first (4 weeks), then start Flutter week 1 (so Flutter week 2 lands when API is fully done)
- Alternative: start Flutter foundations in parallel from API week 2 onwards, lose only 1 week of buffer

---

## 16.3 Total Timeline

| Phase | Weeks | Running total |
|-------|-------|---------------|
| 2a — API Layer | 4.5 | 4.5 |
| 2b — Flutter MVP | 12 | 16.5 |
| 2c — Launch | 4 | 20.5 |
| **Total** | | **~20–21 weeks (~5 months)** |

Assumes solo builder (you + me) with design screenshots in hand. Extend by 2–4 weeks for: unforeseen Razorpay issues, Play Store review delays, new feature creep.

**Note on Phase 2a's 4 → 4.5 weeks:** on April 24, 2026 the UI-Safe API
bar was raised (see [reference/ui-safe-api-checklist.md](../reference/ui-safe-api-checklist.md)).
Weeks 3 + 4 now ship with Pest tests for every endpoint, Bruno collection,
contract snapshot tests, and a Scribe completeness audit. Added ~3 days
of quality-bar work across the two weeks. The investment pays back
heavily in Phase 2b — zero API-shape surprises when Flutter starts.

---

## 16.4 Go/No-Go Gates

Between phases, check these before proceeding:

### Gate A: API Layer → Flutter (end of week 4.5)
- [ ] All `/api/v1/*` endpoints return correct envelope
- [ ] Pest suite green (target: 150+ tests — 1 happy + 2+ error paths per endpoint)
- [ ] Contract snapshot tests all green (step-17 — the shape tripwire)
- [ ] Bruno collection runs green via `bru run` (step-16)
- [ ] Scribe completeness test passes (step-18 — every endpoint has `@response` + error cases)
- [ ] Production deploy of API has no web regression
- [ ] Error codes consistent and documented
- [ ] Rate limits enforced and tested
- [ ] Razorpay test-mode flow: order → SDK mock → verify → membership active
- [ ] Push notifications deliver to test FCM token
- [ ] **UI-safe checklist green for every endpoint** (see [reference/ui-safe-api-checklist.md](../reference/ui-safe-api-checklist.md))

**If any unchecked:** delay Flutter start until resolved.

### Gate B: Flutter MVP → Launch (end of week 16)
- [ ] All screens implemented per specs + your screenshots
- [ ] Manual testing checklist passed (§16.7)
- [ ] Release AAB builds without errors
- [ ] Release build installed on physical device, full journey tested
- [ ] Crashlytics setup + test crash visible in console
- [ ] No P0/P1 bugs open
- [ ] Signing keystore backed up in 2 locations

### Gate C: Internal → Production (end of week 18)
- [ ] Internal testing: 10 users complete full flows
- [ ] Crash-free rate on internal track ≥ 99%
- [ ] Closed testing: 50 users tested for 2 weeks
- [ ] Top 5 feedback items addressed
- [ ] In-app reviews mostly positive (sample reviews)

---

## 16.5 Test Plan

### Backend (API Layer)

**Pest integration tests — structure per endpoint:**
```
tests/Feature/Api/V1/AuthTest.php
  ├ test_register_step_1_creates_user_and_token
  ├ test_register_step_1_rejects_duplicate_email
  ├ test_register_step_1_rejects_underage
  ├ test_send_phone_otp_rate_limited
  ├ test_verify_phone_otp_wrong_code
  ├ test_login_password_success
  ├ test_login_password_wrong_credentials
  ├ test_logout_revokes_current_token
  └ ... (one test per endpoint × 2–3 scenarios)
```

**Target coverage:**
- 1 happy path + 1–2 error paths per endpoint (minimum)
- Security cases: authz, authn, same-gender, blocked, suspended
- Payment: signature verification + webhook idempotency

**Metric:** all Pest tests green before each Phase 2a weekly deliverable.

### Flutter

**Unit tests (`test/`):**
- DTOs fromJson/toJson
- Formatters + validators
- Provider state transitions (mocked repository)

**Widget tests:**
- Shared widgets: ProfileCard variants, PrimaryButton states, LoadingSkeleton
- Critical forms: login, register step 1 validation

**Integration tests (`integration_test/`):**
- Login happy path
- Register step 1→5 (mocked API)
- Send interest flow
- Open chat thread + reply
- Razorpay checkout (mocked SDK response)

**Manual testing on physical device:**
- Every screen rendered correctly at 2 screen sizes (small phone, large phone)
- Deep links from various sources (browser, Gmail, notification)
- Offline mode handling
- Background/foreground transitions during payment

---

## 16.6 Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Hostinger shared hosting can't run queue workers for push dispatch | High | Blocks push | Use `database` queue driver + `php artisan queue:work` via Hostinger's cron; or graduate to VPS during Phase 2a |
| Razorpay SDK quirks on new Android versions | Medium | Blocks payment | Test on Android 14/13 early; keep `razorpay_flutter` version pinned once working |
| Keystore for existing webview app is lost | Medium | Can't update existing app listing | Check Play Console for Play App Signing enrollment week 1; if lost, create new listing (lose install base but preserve web brand) |
| Deep link `assetlinks.json` verification fails | Low | Links open browser not app | Test with `adb shell pm verify-app-links` on Android 14 before submit |
| Flutter SDK breaking change in dependencies | Medium | Build breaks | Pin exact versions once a build is working; upgrade only between phases |
| Site config fetch slow on launch (3s timeout insufficient) | Medium | Blank splash | Cache last-known site config in Hive; use cached immediately + revalidate in background |
| Play Store review rejection (content policy) | Low | 1–2 week delay | Review Google's Dating & Matrimony policy before submit; ensure ID verification is declared |
| API versioning drift (a field renamed on web controller that API reuses) | Medium | Flutter breaks | Enforce via contract tests in Pest that snapshot envelope shape |
| FCM deliverability (new Android restrictions) | Low | Missed notifications | Use high-priority for critical types; respect Android 13 runtime permission; monitor via metrics |
| Photo upload fails on slow connections | Medium | User frustration | Compress client-side to < 2 MB; show upload progress; retry with backoff |
| Coupon abuse (user creates multiple accounts) | Low | Revenue leak | Per-user usage tracking already in `coupon_usages`; add phone/device fingerprint check if exploited |
| Razorpay webhook missed (firewall / DNS) | Low | Subscription stays pending | `/verify` endpoint handles success case; webhook is just fallback for edge cases |

---

## 16.7 Manual Testing Checklist

Before Phase 2c rollout, manually verify:

### Auth flow
- [ ] Fresh install → onboarding slides → login screen
- [ ] Register step 1 → creates user, token stored
- [ ] Register step 2–5 → each step persists and navigates forward
- [ ] Email OTP verification → succeeds with valid code
- [ ] Phone OTP verification → succeeds with valid code
- [ ] Login via password → dashboard
- [ ] Login via phone OTP → dashboard
- [ ] Login via email OTP → dashboard
- [ ] Forgot password → email sent → reset link opens app → new password works
- [ ] Logout → login screen, token cleared
- [ ] Biometric enrol → next launch → fingerprint unlock → dashboard

### Core flows
- [ ] Dashboard loads all sections
- [ ] Search with 5+ filters → results match expected
- [ ] Discover → category → results
- [ ] Profile view loads, tabs swipeable, match score visible
- [ ] Edit own profile section → saves, completion % increases
- [ ] Upload profile photo → approved/pending status shown
- [ ] Set primary photo → reflects everywhere
- [ ] Delete photo → archived → restorable
- [ ] Send interest → receiver notification received
- [ ] Accept interest → sender notification received
- [ ] Reply in chat → polling picks up new message within 10s
- [ ] Shortlist toggle → appears in shortlist
- [ ] Who viewed me (premium) → shows viewers
- [ ] Block profile → hidden from search + interest flows
- [ ] Report profile → submits, admin sees

### Membership
- [ ] Plans screen shows current + all plans
- [ ] Coupon apply → discount shown
- [ ] Checkout → Razorpay opens → test card → success → membership active
- [ ] Receipt PDF downloads
- [ ] 100% coupon → skips Razorpay, activates directly
- [ ] Payment history shows past subscriptions

### Settings
- [ ] Visibility toggles save
- [ ] Alerts toggles save
- [ ] Change password → other devices logged out
- [ ] Hide profile → not in search
- [ ] ID proof upload → pending state
- [ ] Delete account → soft-deleted + logged out

### Push / deep links
- [ ] Receive test push notification
- [ ] Tap notification (app foreground) → correct screen
- [ ] Tap notification (app background) → correct screen
- [ ] Tap notification (app terminated) → cold start to correct screen
- [ ] Deep link `https://kudlamatrimony.com/profiles/AM100042` → profile opens
- [ ] Affiliate `?ref=MNG` preserved through registration

### Offline / edge cases
- [ ] Offline banner appears on network loss
- [ ] Cached dashboard shows immediately on relaunch
- [ ] App version below minimum → update-required screen
- [ ] Screen rotation doesn't crash any screen
- [ ] Deep link while app terminated cold-starts to correct screen

---

## 16.8 Deliverables

### Part A — API Layer (Laravel)
- ~20 new controllers under `App\Http\Controllers\Api\V1\`
- ~15 new Resource classes under `App\Http\Resources\V1\`
- ~20 FormRequest classes
- Extracted services: `AuthService`, `RegistrationService`, `OnboardingService`, `PhotoAccessService`, (reuse existing: `OtpService`, `InterestService`, `MatchingService`, `PaymentService`, `NotificationService`)
- Migrations: `personal_access_tokens` (Sanctum), `otp_verifications.channel`, `devices`, `photo_access_grants`, `broadcast_delivery_log`
- `routes/api.php` with ~80 endpoints
- Pest test suite covering every endpoint
- Firebase service account JSON + config
- Updated `ScheduleKernel` with new jobs (interest expiry, saved search alerts, notification health check)

### Part B — Flutter App
- `flutter-app/` folder with full Flutter project
- 30+ screens organized by feature
- Riverpod providers + repositories per feature
- Shared widget library (ProfileCard, buttons, states, skeletons)
- Signed release AAB
- `google-services.json` + `firebase_options.dart`
- ProGuard rules
- Play Store listing assets (icon, screenshots, feature graphic, descriptions)

### Part C — Documentation updates
- Update `README.md` with mobile app quick start
- Update `NEXT_SESSION_PLAN.md` — Phase 2 → DONE ✓
- Update `TECH_STACK.md` with Flutter + Sanctum + Firebase
- Create `docs/api/` with OpenAPI/Postman collection export (optional, Phase 3 polish)

---

## 16.9 Post-launch Roadmap

After Phase 2 ships, unblocks:

| Item | When |
|------|------|
| iOS build (requires Apple Developer $99/year) | Month 6–7 |
| Laravel Reverb real-time chat (requires VPS migration) | Month 7–8 |
| Bulk CSV import (Phase 3) | Month 8 |
| Installation wizard + Envato licensing (Phase 3) | Month 9 |
| Wedding Directory module (Phase 3) | Month 10–11 |
| Meilisearch at 50K+ profiles | When needed |
| Localisation (Hindi, Kannada, Tulu, Malayalam) | As paid add-on |

See `NEXT_SESSION_PLAN.md` for the full long-term roadmap.

---

## 16.10 Getting Started

Ready to kick off Phase 2a week 1?

Order of operations:
1. **Confirm screen designs status.** You said you have screenshots for all pages. Great — we'll pull them screen-by-screen as we reach each.
2. **Firebase project** — do you already have one for this app, or do I create fresh? (If existing, share the project name.)
3. **Razorpay credentials** — we'll use test mode for dev. Confirm: current production Razorpay key is kept for web, we use a parallel test key for mobile dev.
4. **Keystore for old webview app** — can you check Play Console (or your keystore archive) to confirm whether you have it? If yes, we migrate in-place. If no, we launch as a new listing.
5. **Hostinger queue worker** — check if they allow `php artisan queue:work` via cron. Required for push dispatch to not block API requests. If not, we need to discuss alternatives.

Once those answers are in, I'll start with **Phase 2a Week 1 — install Sanctum, publish routes/api.php, wire the envelope + ApiExceptionHandler + the public `/site/settings` endpoint**. That unblocks Flutter project setup in parallel.

Ping me with the screenshot for **Splash + Onboarding Slides + Login** whenever you want to start Flutter work alongside the API.
