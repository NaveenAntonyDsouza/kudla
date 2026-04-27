# Phase 2a — Week 1: Foundations

**Goal:** stand up the scaffolding every future endpoint sits on. Do this once, get it right.

**Prerequisite:** [`00-decisions-and-context.md`](../../00-decisions-and-context.md) understood.

**Design reference:** [`design/01-api-foundations.md`](../../design/01-api-foundations.md)

---

## Steps

Work through these in order. Each is 30 min–2 hours.

| # | Step | Status | Commit |
|---|------|--------|--------|
| 1 | [Install Laravel Sanctum](step-01-install-sanctum.md) | ✅ | `ea2791c` |
| 2 | [Create `routes/api.php` skeleton](step-02-api-routes-skeleton.md) | ✅ | `729cbad` |
| 3 | [Build response envelope helper (`ApiResponse`)](step-03-response-envelope.md) | ✅ | `455b7f2` |
| 4 | [Exception handler for API errors](step-04-api-exception-handler.md) | ✅ | `f23c213` |
| 5 | [`ForceJsonResponse` middleware](step-05-force-json-middleware.md) | ✅ | `f2fadc2` |
| 6 | [First public endpoint — `GET /api/v1/site/settings`](step-06-site-settings-endpoint.md) | ✅ | `3dd2a4b` |
| 7 | [Reference data endpoints — `/api/v1/reference/*`](step-07-reference-data-endpoints.md) | ✅ | `4e3503f` |
| 8 | [Install Scribe for API docs](step-08-scribe-installation.md) | ✅ | `9024b22` |

**Week 1 shipped April 23, 2026.** 10 commits total on `phase-2-mobile` (8 steps + plan + TECH_STACK update). Tests: 18/18 Pest green, 119 assertions.

**End-of-week acceptance:** [week-01-acceptance.md](week-01-acceptance.md)

## Deltas from the plan (what we learned during execution)

- **install:api is non-interactive-friendly only via `--no-interaction`.** The shell here auto-backgrounds commands; needed explicit flag.
- **`ReferenceDataService` API is simpler than the step file assumed** — no built-in religion→caste cascading. The service just has `get()` / `getFlat()` / `getOptions()`. I kept the cascading concept for future (via the `VALID_LISTS` allow-list + query params), but didn't implement religion→caste filtering since the config doesn't carry that relationship. Genuine cascading will be added in Week 3 when we build search filters.
- **`SiteSetting::setValue()` not `set()`** — step file said `set()`; actual method is `setValue()`.
- **Local DB migration drift** — 8 migrations marked "Pending" locally but columns exist. Used `--path=<specific-migration>` to run only our Sanctum migration. Continuing with this pattern for Phase 2a.
- **SQLite `:memory:` test DB can't run MySQL-specific migrations** (FULLTEXT). Kept `RefreshDatabase` disabled in `tests/Pest.php`. Tests pre-seed their cache/data to bypass DB. Full MySQL test DB setup lands Week 2 when auth tests genuinely need it.
- **Scribe needs `composer dump-autoload` right after install** — otherwise `Knuckles\Scribe\TestSuite` isn't autoloaded yet.

These deltas are documented in each step's commit message.

---

## What you'll have at end of week 1

- A working `/api/v1/` routing layer with Sanctum auth enabled on protected routes
- Every response uses the locked envelope shape from `00-decisions` (success/data or error/code)
- Every exception — validation, not-found, throttled, anything uncaught — maps to a typed error code
- Two fully working public endpoints:
  - `GET /api/v1/site/settings` — Flutter fetches this on every launch for theme/config
  - `GET /api/v1/reference/{list}` — cascading dropdowns (religions, castes, etc.)
- Scribe generating human-readable API docs at `https://kudlamatrimony.com/docs`
- Pest tests for the envelope shape contract (fails if any endpoint drifts)

## What you won't have yet

- Auth endpoints — that's week 2
- Profile / photo / search endpoints — that's week 3
- Interests / payments / push — that's week 4

---

## Time budget

| Step | Estimated time |
|------|----------------|
| 1 — Sanctum install | 30 min |
| 2 — routes/api.php skeleton | 30 min |
| 3 — Response envelope | 1 hour |
| 4 — Exception handler | 1.5 hours |
| 5 — ForceJson middleware | 20 min |
| 6 — Site settings endpoint | 1.5 hours |
| 7 — Reference data endpoints | 2 hours |
| 8 — Scribe setup | 1 hour |
| **Total** | **~8 hours = 1.5 focused days** |

Slack for debugging + commits: budget **2 full working days** for week 1.

---

## Git workflow for this week

1. Create branch: `git checkout -b phase-2-mobile`
2. After each step, commit: `git commit -m "phase-2a wk-01: step-NN [title]"`
3. At end of week, verify acceptance criteria, then: `git push origin phase-2-mobile`

**No merging to `main` mid-phase.** Phase 2a ships as one unit after exit criteria pass.

---

**Start:** [Step 1 — Install Sanctum →](step-01-install-sanctum.md)
