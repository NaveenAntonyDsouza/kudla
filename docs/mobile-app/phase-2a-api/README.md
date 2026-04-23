# Phase 2a — REST API Layer

**Duration:** 4 weeks (+ 1 week buffer)
**Goal:** Build a complete JSON REST API under `/api/v1/*` that the Flutter app (and CodeCanyon buyers' mobile apps) can call. Zero business-logic rewrites — thin controllers call existing `App\Services\*`.

**Prerequisites:** `00-decisions-and-context.md` Part 4 pre-kickoff items all ✅ (except logo cut-outs, which are Phase 2c).

---

## Week-by-Week

| Week | Folder | Goal | Deliverables |
|------|--------|------|--------------|
| 1 | [week-01-foundations](week-01-foundations/README.md) | Scaffolding that every endpoint sits on | Sanctum installed, `routes/api.php`, response envelope, exception handler, first public endpoints (`/site/settings`, `/reference/*`), Scribe setup |
| 2 | [week-02-auth-registration](week-02-auth-registration/README.md) | Authentication + 5-step registration + OTP + login | OTP channel migration, `AuthService` + `RegistrationService` extracted, all `/auth/*` endpoints, device registration, forgot password |
| 3 | [week-03-profiles-photos-search](week-03-profiles-photos-search/README.md) | Profile, photo, search, discover endpoints | Dashboard, profile view/edit, photo upload, photo requests, partner search, discover hub, match score |
| 4 | [week-04-interests-payment-push](week-04-interests-payment-push/README.md) | Interests, chat, membership, Razorpay, push | Interest lifecycle, chat polling, plans + Razorpay + verify, webhook, FCM device registration, notification dispatch, all engagement endpoints |

**Buffer week (between 2a and 2b):** Bruno test collection run, security pass, Scribe docs published, load test on hot endpoints.

---

## Exit Criteria (Go/No-Go to Phase 2b)

- [ ] All ~80 endpoints in `reference/endpoint-catalogue.md` return correct envelope
- [ ] Pest test suite passes with ≥ 100 tests (one happy path + one error per endpoint minimum)
- [ ] Production deploy of API has zero web regressions
- [ ] Razorpay test-mode end-to-end: order → verify → membership active
- [ ] Push notification delivers to a test FCM token
- [ ] Rate limits enforced on OTP/login/upload
- [ ] Scribe OpenAPI 3.1 spec published at `/docs` (admin-gated in prod)
- [ ] Bruno collection covers every endpoint (for manual smoke testing)
- [ ] Load test: `/api/v1/search` handles 100 req/s for 5 min without degradation

## What's NOT in Phase 2a

- Any Flutter code (that's Phase 2b)
- iOS-specific API behaviour (same endpoints serve both platforms)
- WebSocket / Reverb real-time (Phase 3)
- Admin panel changes (already complete)

---

## How to execute

Work through weeks sequentially. Within each week:

1. Open `week-NN-*/README.md` — shows step list and goal
2. Open `step-NN-*.md` — follow Goal / Prerequisites / Procedure / Verification
3. Commit after each step with message: `phase-2a wk-N: step-NN [title]`
4. Mark step ✅ in the week README
5. Move to next step

---

## Quick Reference

- [All API endpoints (flat list)](../reference/endpoint-catalogue.md)
- [All error codes](../reference/error-codes.md)
- [Version pins (April 2026)](../reference/version-pins.md)
- [Troubleshooting](../reference/troubleshooting.md)

---

**Next action:** [Start Week 1 →](week-01-foundations/README.md)
