# Mobile App Plan — Master Index

**Platform:** MatrimonyTheme (Laravel 13 + Filament admin + Flutter mobile app)
**Target:** Native Flutter Android app, iOS deferred to Phase 3
**Timeline:** ~20–22 weeks from Phase 2a kickoff → Play Store production rollout
**Plan version:** 2.0 (April 23, 2026)

---

## How This Plan Is Organised

```
docs/mobile-app/
├── README.md                    ← you are here (master index)
├── 00-decisions-and-context.md  ← all big decisions, why we made them, what's locked
├── CHANGELOG.md                 ← plan revision history
│
├── phase-2a-api/                ← 4 weeks: build the REST API
│   ├── README.md
│   ├── week-01-foundations/
│   ├── week-02-auth-registration/
│   ├── week-03-profiles-photos-search/
│   └── week-04-interests-payment-push/
│
├── phase-2b-flutter/            ← 12 weeks: build the Flutter app
│   ├── README.md
│   ├── week-01-scaffold/
│   ├── week-02-auth-ui/
│   ├── week-03-registration-ui/
│   ├── week-04-dashboard-profile-view/
│   ├── week-05-search-discover/
│   ├── week-06-my-profile-edit/
│   ├── week-07-photo-manager/
│   ├── week-08-interests-inbox/
│   ├── week-09-chat-thread/
│   ├── week-10-membership-razorpay/
│   ├── week-11-settings-notifications/
│   └── week-12-polish/
│
├── phase-2c-launch/             ← 4 weeks: Play Store rollout
│   ├── README.md
│   └── step-01 … step-10
│
├── design/                      ← original 16 design docs (authoritative spec)
│   ├── README.md
│   └── 01-api-foundations.md … 16-implementation.md
│
└── reference/                   ← cross-cutting quick-lookup tables
    ├── README.md
    ├── version-pins.md          ← exact package versions (April 2026)
    ├── endpoint-catalogue.md    ← all ~80 API endpoints in one table
    ├── screen-catalogue.md      ← all ~40 Flutter screens in one table
    ├── error-codes.md           ← all API error codes
    ├── glossary.md              ← terms, acronyms, package names
    └── troubleshooting.md       ← common pitfalls + fixes
```

---

## How To Use This Plan

### Starting fresh?

1. Read [`00-decisions-and-context.md`](00-decisions-and-context.md) — 10 min to catch up on all the "why" questions
2. Skim [`design/README.md`](design/README.md) — the architectural big picture
3. Open [`phase-2a-api/README.md`](phase-2a-api/README.md) and start week 1

### Picking up mid-plan?

1. Check [`CHANGELOG.md`](CHANGELOG.md) for recent updates
2. Find your current week folder (`phase-2X-*/week-NN-*`)
3. Open that week's `README.md` to see which steps are done vs pending
4. Start the next `step-NN-*.md`

### Step file conventions

Every step file follows this structure so you can execute without re-reading long docs:

```
# Step NN — [Short Verb-Phrase Title]

## Goal
One sentence: what this step produces.

## Prerequisites
- [ ] Previous step N-1 completed
- [ ] Any env vars, keys, tools ready

## Procedure
1. Run this command
2. Edit this file (with exact diff or before/after code)
3. Test with curl / flutter test

## Verification
- [ ] Specific, observable outcome
- [ ] Second outcome

## Common issues
- **Error X:** fix with Y
- **Unexpected behaviour Z:** check W

## Next step
→ step-NN+1-foo.md
```

Each step = 30 min to 2 hours of focused work. If a step would take more than 2 hours, it's broken into sub-steps.

---

## Phase Kickoff Prerequisites

Before starting **any** implementation:

- ✅ Firebase project `kudla-matrimony-e3d63` created, `google-services.json` downloaded
- ✅ Razorpay test keys generated (kept by user, will share when Phase 2a Week 4)
- ✅ Play Store "Signing by Google Play" confirmed — update-in-place is safe
- ✅ Logo received (flat PNG); hearts-only icon + source file to come later
- ✅ Hostinger SSH + cron confirmed working for queue worker pattern
- ⏳ **Kickoff signal from you** to start Phase 2a Week 1

---

## Quality Bar — UI-Safe API

Every endpoint from Phase 2a Week 3 onwards must meet the
**[UI-Safe API Checklist](reference/ui-safe-api-checklist.md)** —
8 non-negotiable points that guarantee Flutter development in Phase 2b
hits zero friction on the API boundary:

1. ISO 8601 timestamps (never Carbon, never unix int)
2. Real PHP `bool` (never `"1"` / `"0"` strings)
3. `[]` when empty (never `null`, never missing)
4. Optional fields always present (with explicit `null`)
5. Absolute photo URLs (via `PhotoStorageService`)
6. Every error code has a Scribe `@response`
7. Identical pagination meta across all lists
8. Pest: 1 happy + 2+ error paths per endpoint

Operationalized by **Week 4 steps 16–18** — Bruno collection, contract
snapshot tests, Scribe completeness audit.

---

## Decisions Locked

See [`00-decisions-and-context.md`](00-decisions-and-context.md) for full reasoning. Summary:

| Area | Choice |
|------|--------|
| API layer pattern | Thin controllers under `App\Http\Controllers\Api\V1\` calling existing `App\Services\*` |
| Auth | Laravel Sanctum personal access tokens, Bearer header |
| State management | Riverpod 3.3.1 (with offline persistence) |
| Routing | go_router 17.2.2 |
| HTTP client | Dio 5.9.2 |
| Push | Firebase Cloud Messaging direct (`firebase_messaging` 16.2.0) |
| Payments | `razorpay_flutter` 1.4.4 native SDK |
| Real-time chat | Polling every 10s (Reverb deferred to Phase 3 post-VPS) |
| Local storage | `flutter_secure_storage` (sensitive) + `hive_ce` (cache) |
| Android target SDK | 36 (Android 16) — ahead of August 2026 deadline |
| Android min SDK | 21 (Android 5.0) |
| Launch platforms | Android v1 (in-place update to `com.books.KudlaMatrimony`); iOS v2 |
| API docs | Scribe (generates Postman + OpenAPI 3.1 + HTML) |
| Testing | Pest v4 (Laravel) + flutter_test/integration_test (Flutter) |
| Linting | Laravel Pint + very_good_analysis (Flutter) |

---

## What's NOT In This Plan (Tracked Elsewhere)

These are real things, handled after Phase 2:

- iOS build (Phase 3, needs Apple Developer $99/year)
- Laravel Reverb real-time chat (Phase 3, needs VPS)
- Bulk CSV import (Phase 3)
- Installation wizard + Envato licensing (Phase 3)
- Wedding Directory module (Phase 3)
- Meilisearch upgrade (when >50K profiles)
- Localisation (Phase 4 or paid add-on)

See the main [`../NEXT_SESSION_PLAN.md`](../NEXT_SESSION_PLAN.md) for the full long-term roadmap.

---

## Quick Links

- [All decisions explained](00-decisions-and-context.md)
- [Start Phase 2a](phase-2a-api/README.md)
- [All 80 API endpoints](reference/endpoint-catalogue.md)
- [All 40 Flutter screens](reference/screen-catalogue.md)
- [Version pins (April 2026)](reference/version-pins.md)
- [Design reference (original 16 docs)](design/README.md)
