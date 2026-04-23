# MatrimonyTheme — Feature Status
**Last Updated:** April 23, 2026
**Live deployment:** kudlamatrimony.com (deployed April 23, 2026 — all features below are LIVE)

---

## Core Platform

| Feature | Status |
|---------|--------|
| Registration (5 steps) + Email/Phone OTP verification | Done |
| Login (email/password + OTP) + Forgot Password | Done |
| Onboarding (4 steps: personal, location, preferences, lifestyle) | Done |
| Dashboard (7 sections: CTA, recommended, stats, mutual, views, newly joined, discover) | Done |
| Photo upload & management (profile, album, family) + photo privacy (3 modes) | Done |
| Photo watermark (GD library, diagonal repeating text) | Done |
| Photo multi-driver storage (Local / Cloudinary / R2 / S3) with hybrid mode | Done |
| Photo cropping (Cropper.js: crop box, rotate L/R, flip horizontal, brightness, 3:4 aspect lock for profile) + Intervention Image v4 processing | Done (verified live April 23) |
| WebP output format (25-35% smaller than JPEG) | Done |
| View & Edit My Profile (9 accordion sections with inline editing) | Done |
| Profile Preview (4 tabbed sections) + Print Profile | Done |
| Public profile view (privacy on contacts + match score breakdown) | Done |
| Profile approval enforcement (pending approval banner, admin setting) | Done |
| Profile completion calculation (with photo weight) | Done |

## Search & Discovery

| Feature | Status |
|---------|--------|
| Partner Search (15+ filters) + Keyword Search + Search by ID | Done |
| Load Partner Preferences on search form | Done |
| Save Search (save, load, delete from search results) | Done |
| Search ranking: Diamond (highlighted) -> Premium -> Recently Active -> Newest | Done |
| Sort options: Relevance, Newest First, Recently Active, Age Low/High | Done |
| Quick Search via homepage community links (religion, caste, denomination params) | Done |
| Matchmaking Engine (12-criteria weighted scoring, My Matches, Mutual Matches) | Done |
| Discover Profiles (13 categories, 3-level browsing, public for SEO) | Done |

## Communication & Interaction

| Feature | Status |
|---------|--------|
| Interest Send/Accept/Decline + Chat (inbox optimized: 13->2 queries) | Done |
| Chat restricted to premium users only | Done |
| Photo Requests: send/approve/ignore with blurred photos + privacy overlays | Done |
| In-app notifications (bell icon dropdown + full page) | Done |
| Email notifications (interest notifications, membership expiry reminders) | Done |
| Report Profile (7 reasons, pending admin review) | Done |
| Success Stories (user submission + admin approval) | Done |

## Membership & Payments

| Feature | Status |
|---------|--------|
| 5 Plans: Free, Silver, Gold, Diamond, Diamond Plus | Done |
| Razorpay payment integration | Done |
| Contact view: requires BOTH isPremium() AND accepted interest | Done |
| Chat: premium only (free users see upgrade CTA) | Done |
| Plan-based daily interest limits (5/10/20/50) | Done |
| Premium badge + highlighted profiles in search | Done |
| Who Viewed Me: premium gate (free users see count only) | Done |
| Membership expiry reminders (3-day + expiry day, email + in-app) | Done |
| Membership expiry auto-deactivation (daily scheduled command) | Done |
| Coupon/Discount system (flat INR or %, plan restrictions, 100% discount handling) | Done |

## Admin Panel (Filament 5.4.3)

| Feature | Status |
|---------|--------|
| Dashboard: 10+ widgets with lazy loading + caching + time-period tabs | Done |
| User Management: Card-style list, 9 tab filters, 14 sidebar filters | Done |
| User Management: Actions (View, Edit, WhatsApp, Approve, Add Note, Activate/Deactivate) | Done |
| User Management: View page (8 tabs covering all profile data + admin notes) | Done |
| User Management: Edit page (9 sections covering all 7 related tables) | Done |
| Admin Notes with follow-up dates + overdue badges | Done |
| ID Verification resource page | Done |
| Membership Management resource page | Done |
| Site Settings (name, tagline, contact, social links, profile ID prefix) | Done |
| Content Management (FAQs, email templates, notification templates, static pages, reference data) | Done |
| Advertisement Management (5 ad slots, image/HTML/AdSense, click tracking) | Done |
| Moderation (suspend/ban, contact inbox with DB persistence, canned responses) | Done |
| Reports & Analytics (user reports, engagement reports, revenue reports, all with charts + CSV export) | Done |
| System Health & Maintenance (PHP/MySQL info, cache buttons, error log viewer) | Done |
| Database Backup (pure PHP via PDO, StreamedResponse, works on shared hosting) | Done |
| Activity Log Viewer (read-only resource + LogsAdminActivity trait on key admin actions) | Done |
| Gateway Settings (SMTP / SMS / Razorpay configurable from admin panel) | Done |
| Bulk CSV Import (resource scaffold — workflow refinement pending) | Partial |

## Staff / Telecaller Module (Phase 1.3)

| Feature | Status |
|---------|--------|
| Staff roles (Telecaller, Branch Staff, Branch Manager, Support, Finance, Moderator, etc.) with permission matrix | Done |
| Staff Management (CRUD with role + branch + active status) | Done |
| Staff login to Filament admin with restricted permissions | Done |
| Lead Management (CRUD with status flow New → Contacted → Interested → Registered → Lost) | Done |
| Call Logs (CRUD linked to lead/profile, call_type, duration, outcome, follow-up) | Done |
| Register on Behalf (staff fills registration for walk-in users; sends credentials) | Done |
| Staff personal dashboard (My Leads, My Calls, My Registrations, My Follow-ups) | Done |
| Staff performance widgets (conversion funnel, call activity chart, stats overview) | Done |
| **Staff Targets & Incentives (Phase 1.3.5)** — monthly targets per staff + live incentive calculation | Done |

## Franchise / Branch Module (Phase 1.4)

| Feature | Status |
|---------|--------|
| Branch CRUD (name, code, location, manager, commission %) | Done |
| Branch assignment on users & profiles (auto via affiliate link ?ref=CODE) | Done |
| Branch affiliate links + QR code generation + short URL tracking | Done |
| Affiliate click tracking (clicks, registrations, conversions per link) | Done |
| Branch Manager role — scoped access to only their branch's staff/members/revenue | Done |
| Branch dashboard widgets (overview, leads funnel, revenue chart, staff performance, affiliate stats) | Done |
| Branch Revenue & Commission — gross revenue, commission %, payout calculation | Done |
| BranchPayout model + admin CRUD for payout tracking | Done |

## Theme & Branding (Phase 2.6)

| Feature | Status |
|---------|--------|
| Site identity (name, tagline, contact, LinkedIn, announcement banner, stats auto-compute toggle) | Done |
| 8 preset color palettes (Royal Purple, Rose Gold, Ocean Blue, Emerald Green, Sunset Orange, Midnight Blue, Cherry Red, Sage) | Done |
| Custom color picker (any hex) overrides preset | Done |
| Email template theming (brand colors + logo auto-injected into all emails) | Done |
| 10 curated Google Fonts (5 heading + 5 body) + custom Google Font name input | Done |
| Logo + Favicon upload with preview | Done |
| **3 homepage design templates** (Classic / Modern / Premium) — admin picks via radio, all share same content | Done (April 23) |

## Re-engagement & Growth (Quick Wins)

| Feature | Status |
|---------|--------|
| Re-engagement emails (7-day, 14-day, 30-day inactive, daily 9 AM, master switch in SiteSettings) | Done |
| Weekly match suggestion emails (Sunday 10 AM, configurable count, master switch) | Done |
| Profile completion nudges (daily 7 PM, threshold configurable, master switch) | Done |
| Unsubscribe controller + one-click unsubscribe flow | Done |
| Admin dashboards for each (Reengagement / WeeklyMatches / ProfileNudge) — inspect & trigger | Done |

## SEO & Performance

| Feature | Status |
|---------|--------|
| Dynamic meta tags (title, description, canonical, OG, Twitter Cards) | Done |
| Structured data (Organization + WebSite JSON-LD) | Done |
| Dynamic XML sitemap (30+ URLs) with admin toggle | Done |
| robots.txt + .htaccess (HTTPS, GZIP, caching, redirects) | Done |

## Static Pages

| Feature | Status |
|---------|--------|
| Help/FAQ page (5 categories, 20 questions) | Done |
| Contact Us page (form + email + Google Maps embed) | Done |
| Privacy Policy + Terms of Service | Done |
| Child Safety Policy + Report Misuse + Advertise With Us | Done |
| Admin-editable WYSIWYG static pages with variable substitution | Done |
| Homepage (registration form, success stories, community browse) | Done |

## Registration Validation (Mandatory Fields)

| Field | Rule |
|-------|------|
| Religion | Required (Step 2) |
| Denomination | Required if Christian |
| Caste | Required if Hindu/Jain |
| Muslim Sect | Required if Muslim |
| Mother Tongue | Required (Onboarding) |
| Native State/District | Required if India (Step 4) |
| Custodian Name/Relation | Optional |

---

## Pending Admin Panel Sections (Historical — Reclassified)

| # | Section | Original Priority | Current Status |
|---|---------|-------------------|----------------|
| 3 | Verification (ID Proofs) enhancement | Next | Done |
| 4 | Membership & Payments enhancement | High | Done |
| 5 | Content Management (reference data, pages, templates) | Medium | Done |
| 6 | Interest & Match Management | Medium | Done |
| 7 | Moderation (photos, reports, blocks) | Medium | Done |
| 8 | Reports & Analytics (charts, CSV export) | Medium | Done |
| 9 | System (install wizard, updater) | For CodeCanyon | **Pending** |
| 10 | Franchise / Branch Management | For CodeCanyon | Done |
| 11 | Staff / Telecaller Module | For CodeCanyon | Done |
| 12 | Advertisement Management | For CodeCanyon | Done |
| 13 | Wedding Directory | Phase 2 | **Pending** |

---

## Remaining for CodeCanyon Launch (deferred — after native app)

> CodeCanyon packaging is intentionally deferred until the Flutter native app lands. See "Current Focus" section below for rationale. These items are tracked here for completeness.

| Item | Effort | Notes |
|------|--------|-------|
| **Installation Wizard** (5-step setup: env check, DB config, super admin creation, license activation, quick settings) | 1-2 days | Not built |
| **System Update/Installer** (ZIP upload, auto-backup, extract, run migrations) | 1-2 days | Not built |
| **Bulk CSV Import UI polish** (download template → validate → preview → import → error report CSV) | 0.5-1 day | Resource scaffold exists; workflow incomplete |
| ~~**Demo data seeder**~~ | **Done** (April 22) | `matrimony:demo-seed` + `matrimony:demo-clean` commands; 50 profiles with GD-generated avatars, 30 leads, 100 call logs, 15 subscriptions, 6 staff targets, 20 interests, 30 profile views, 3 testimonials. All tagged with markers for safe removal. |
| **CodeCanyon listing assets** (screenshots using seeded demo, promo video, feature list) | 1 day | Will include mobile app screenshots once shipped |
| **QA smoke-test pass on live deployment** | 0.5 day | Deferred since April 19 branch push |

## Deferred by Design

| Item | Reason |
|------|--------|
| **Horoscope Matching UI integration** | Infrastructure fully built (27-nakshatra matrix editor, algorithm, document approval). Match weight defaults to 0 (opt-in) so horoscope contributes nothing to match scores unless admin enables it. Wise default for a multi-community CodeCanyon template — buyers targeting Christian/Muslim communities shouldn't see it; buyers targeting Hindu communities flip one toggle. No UI in user-facing forms by design. |

## Current Focus — Native Mobile App (Flutter)

**This is the next priority** — chosen over CodeCanyon packaging because:
1. Existing webview app `com.books.KudlaMatrimony` is live but limited (no push, no biometric, limited native UX)
2. Once native app lands, CodeCanyon listing commands premium tier ("Web + Flutter App" bundle)
3. Flutter gives Android + iOS from one codebase

**Detailed spec:** [MOBILE_APP_PLAN.md](MOBILE_APP_PLAN.md)

| Step | Scope | Status |
|------|-------|--------|
| 5a. REST API layer (Sanctum + `/api/v1/` JSON routes + FCM tokens) | 1-2 weeks | Pending |
| 5b. Flutter project setup + auth screens | 1-2 weeks | Pending |
| 5c. Core screens (dashboard, search, profile view/edit, photos) | 3-4 weeks | Pending |
| 5d. Interactions (interests, chat, shortlist, push notifications) | 3-4 weeks | Pending |
| 5e. Membership + Razorpay Flutter SDK | 1-2 weeks | Pending |
| 5f. Polish (biometric, offline cache, share card) + store publish | 2-4 weeks | Pending |

**Total estimate:** 3-6 months end-to-end

---

## Phase 3 — CodeCanyon Packaging (after mobile app)

| Item | Effort | Notes |
|------|--------|-------|
| **Installation Wizard** (5-step setup: env check, DB config, super admin creation, license activation, quick settings) | 1-2 days | Not built |
| **System Update/Installer** (ZIP upload, auto-backup, extract, run migrations) | 1-2 days | Not built |
| **Bulk CSV Import UI polish** (download template → validate → preview → import → error report CSV) | 0.5-1 day | Resource scaffold exists; workflow incomplete |
| **CodeCanyon listing assets** (screenshots using seeded demo, promo video, feature list) | 1 day | Now unblocked by demo seeder |
| **QA smoke-test pass on live deployment** | 0.5 day | Deferred since April 19 branch push |

---

## Phase 4 / Future Enhancements

| Item | Scope |
|------|-------|
| Multi-language / i18n | Laravel localization, language switcher, editable translations |
| GDPR Compliance Tools | User data export (ZIP), deletion request flow, cookie consent |
| Web Push Notifications | Firebase/OneSignal for browser push (mobile push covered by Flutter app) |
| Real-time Notifications | Laravel Reverb / WebSocket for live bell icon + online indicator |
| Meilisearch Integration | Fast full-text + typo-tolerant search (needs VPS) |
| Wedding Directory | Separate vendor module (Photographers, Venues, Catering, etc.) |

---

## Key Architecture Notes

- **Two subscription tables:** `subscriptions` (Razorpay audit, amount in paise) + `user_memberships` (feature access)
- **Amount in paise:** 99900 = ₹999. Divide by 100 for display.
- **ProfileQueryFilters trait:** Shared base query with ->approved() scope
- **Admin user (id:1):** No profile, redirected to /admin
- **Notifications type:** VARCHAR(50), not ENUM
- **White-label:** Single codebase serves any matrimony domain via SiteSetting
- **Photo storage:** Multi-driver abstraction (Local / Cloudinary / R2 / S3) — each photo remembers its driver (hybrid mode)
- **BranchScopable trait:** Models with branch_id can be scoped via `->forUserBranch()` to Branch Manager's branch
- **BranchScopable applied to:** Profile, Lead, Coupon, StaffTarget, BranchPayout
- **Theme:** Primary/secondary colors, fonts, logo, favicon all stored in `theme_settings` table, injected as CSS variables at runtime
- **Horoscope:** Opt-in via SiteSetting `horoscope_compatibility` + match weight > 0; 27-nakshatra compatibility matrix
- **Discover categories:** `config/discover.php` is config:cache-safe — dynamic subcategory lists live in `App\Services\DiscoverConfigService`. Add new dynamic source: add a method to the service + reference via `'subcategories_source' => 'methodName'` in config.
- **Carbon v3 quirk:** `diffInDays()` / `diffInHours()` return FLOAT (not int like v2). When displaying like `'2d ago'`, cast `(int)` to avoid "2.5020910621759d ago".
- **Engagement email switches:** `reengagement_enabled`, `weekly_matches_enabled`, `profile_nudges_enabled` — defaults to `'1'` (ON) when missing. Always set to `'0'` first on a new deploy, then enable gradually after verification.
- **Production deploy artifacts:** See `docs/DEPLOY_CHECKLIST.md` (runbook), `docs/PRODUCTION_DB_CLEANUP.sql` (pre-flight SQL), `deploy-build.ps1` in project root (Windows ZIP builder).
