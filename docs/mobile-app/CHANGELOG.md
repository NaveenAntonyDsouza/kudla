# Mobile App Plan — Changelog

All plan revisions logged here. Most recent at top.

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
