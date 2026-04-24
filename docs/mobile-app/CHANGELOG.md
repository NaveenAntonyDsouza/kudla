# Mobile App Plan — Changelog

All plan revisions logged here. Most recent at top.

---

## 2.1.0 — 2026-04-24 (afternoon, mid-Phase-2a)

**UI-Safe API bar raised.** After Week 2 shipped, user asked: "can you
build the API so well that there's no problem during UI?" This revision
operationalizes the answer.

### Added
- `reference/ui-safe-api-checklist.md` — the 8-point non-negotiable
  standard every endpoint must meet (timestamps as ISO 8601, real
  booleans, empty arrays not null, present-but-null optionals, absolute
  photo URLs, documented error responses, uniform pagination meta, Pest
  test coverage)
- Week 4 `step-16-bruno-collection.md` — committed Bruno collection with
  `tests {}` blocks asserting envelope + key fields per endpoint.
  Runnable as `bru run docs/bruno/kudla-api-v1 --env local`
- Week 4 `step-17-contract-snapshot-tests.md` — Pest + spatie/pest-plugin-snapshots
  test file capturing every endpoint's response shape. Fails loudly on
  any future drift. Single most valuable regression net for Phase 2b
- Week 4 `step-18-scribe-audit.md` — automated test that verifies every
  `/api/v1/*` endpoint has `@response` blocks (happy + error),
  `@urlParam` / `@queryParam` documented, 100% Scribe coverage
- New entry in reference/README index pointing to the checklist
- "Quality Bar — UI-Safe API" section in root README

### Changed
- Week 3 README now references the UI-safe checklist, time budget bumped
  from ~32h to ~40h (Pest test writing per endpoint)
- Week 4 README's step-15 renamed to "Feature-Complete Smoke + Scribe
  Regen" (was "Bruno + load test" — Bruno moved to step-16, load test
  dropped as premature optimization at 50-profile local scale)
- `design/16-implementation.md` Gate A expanded with 4 new UI-safe
  exit criteria (contract snapshots, Bruno run, Scribe audit,
  8-point checklist per endpoint)
- Phase 2a total: 4 weeks → 4.5 weeks. Phase 2 total: 20 → 20.5 weeks

### Philosophy
The cost of this bar (~3 extra days across Weeks 3+4) pays back
heavily in Phase 2b. Every class of "Flutter broke because API drifted"
regression is caught by a tripwire test long before Flutter sees it.

Retrofit plan: Week 1+2 endpoints audited + raised to the same bar
during the buffer week after Week 4.

---

## 2.0.0 — 2026-04-23 (evening)

**Major restructure.** Moved from 17 flat design docs to phase-based step-by-step folders.

### Final stats
- **113 markdown files**
- **~19,500 total lines**
- **3 phase folders + reference + design + root**

### Added
- `phase-2a-api/` — 4 weekly subfolders, **53 step files** (week-01: 8, week-02: 15, week-03: 15, week-04: 15) + 4 weekly READMEs + 4 acceptance checkpoints + 1 phase README = 62 files
- `phase-2b-flutter/` — 12 weekly READMEs (detailed) + 1 phase README = 13 files. Step files written just-in-time per week as we reach them (user provides design screenshots per screen)
- `phase-2c-launch/` — 10 step files + 1 phase README = 11 files
- `reference/` folder with 6 content files + README = 7 files
- `00-decisions-and-context.md` — consolidated reasoning for every locked decision
- `CHANGELOG.md` (this file)

### Changed
- Moved 16 original design docs to `design/` subfolder (preserved unchanged)
- Root `README.md` rewritten as master index with TOC

### Applied research updates (April 2026 verified versions)
- **Hive → hive_ce** — original `hive` package is unmaintained; `hive_ce` 2.19.3 is the community successor
- **Android target SDK 34 → 36** — Google Play's August 2026 deadline requires Android 16 (SDK 36); we target it from day 1
- **Flutter 3.24 → 3.41.5** — current stable
- **Laravel 12 → 13** — released March 17, 2026 (PHP 8.3+ required)
- **flutter_riverpod 2.5.0 → 3.3.1** — Riverpod 3 brings native offline persistence + auto-retry on failed providers (simplifies our caching plan)
- **go_router 14.2.0 → 17.2.2**
- **dio 5.5.0 → 5.9.2**
- **firebase_messaging 15.0.0 → 16.2.0**
- **image_cropper 8.0.0 → 12.2.1**
- **razorpay_flutter 1.3.7 → 1.4.4**
- **Added Scribe** (`knuckleswtf/scribe`) for API documentation — generates OpenAPI 3.1 + Postman + HTML from Laravel code
- **Added `very_good_analysis`** — stricter Dart lints than flutter_lints
- **Added Pest v4** — replaces raw PHPUnit for Laravel tests
- **Added FVM** — Flutter Version Manager for pinned Flutter version per project

### Decisions captured in `00-decisions-and-context.md`
- Firebase project: `kudla-matrimony-e3d63` (confirmed, google-services.json in hand)
- Razorpay: separate test-mode key for dev (confirmed)
- Play Store signing: "Signing by Google Play" enrolled → update-in-place safe (confirmed from screenshot)
- Hostinger queue worker: cron-based `queue:work --stop-when-empty --max-time=55` every minute (confirmed working)

---

## 1.0.0 — 2026-04-23 (afternoon)

**Initial plan.** Created 17 documents covering REST API layer + Flutter app + launch process.

- `README.md` — overview + decisions
- `01-api-foundations.md` through `10-push-notifications.md` — API reference
- `11-flutter-foundations.md` through `15-flutter-polish-launch.md` — Flutter reference
- `16-implementation.md` — timeline, gates, risks

Total: ~7,000 lines.

See `design/` for preserved originals.
