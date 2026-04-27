# MatrimonyTheme — Tech Stack

**Last updated:** April 23, 2026 (Phase 2a Week 1 in progress — API layer foundations being laid)

This is the project-wide tech stack — covers the live web platform + the in-progress mobile app. For the mobile app's exact pinned versions, see [`docs/mobile-app/reference/version-pins.md`](mobile-app/reference/version-pins.md).

---

## Core Framework

- **Laravel 13.2.0** — PHP full-stack framework (released March 17, 2026)
- **PHP 8.3.30** — Server-side language (GD + Imagick extensions enabled for image processing)
- **MySQL 8.0** — Relational database, 95+ migrations, 50+ tables
- **Eloquent ORM** — Laravel's database abstraction layer

## Frontend (Web)

- **Blade** — Laravel's templating engine (server-rendered HTML)
- **Tailwind CSS 4.2.2** — Utility-first CSS framework (CSS-first config, no `tailwind.config.js`)
- **Alpine.js 3.15.10** — Lightweight JavaScript framework (loaded via Livewire)
- **Livewire 4.2.3** — Dynamic UI components (Alpine.js delivery + asset injection)

## Authentication & Authorization

- **Laravel built-in Auth** — Session-based authentication for web
- **Laravel Sanctum 4.3** — API token authentication for mobile app ***[added April 23, 2026 — Phase 2a Week 1 Step 1]***
  - Personal access tokens (hashed at rest)
  - 90-day expiry (env-configurable via `SANCTUM_EXPIRATION`)
  - `Authorization: Bearer <token>` transport
  - `HasApiTokens` trait on `App\Models\User`
- **Hash (bcrypt)** — Password hashing
- **Spatie Laravel Permission 6.25** — Role-based access control (HasRoles trait on User model)
- **OTP Service** — Phone (Fast2SMS) + Email (SMTP) verification. Table: `otp_verifications`
- **LoginHistory** — Per-login audit: IP, user agent, login type (`password` / `mobile_otp` / `email_otp`)

## API Layer (Mobile App) — Phase 2a ***[NEW]***

All routes live under `/api/v1/*`. Thin controllers under `App\Http\Controllers\Api\V1\*` call existing `App\Services\*` classes. See [`docs/mobile-app/phase-2a-api/`](mobile-app/phase-2a-api/README.md).

| Component | Purpose |
|-----------|---------|
| **Laravel Sanctum 4.3** | Personal access tokens for mobile |
| **`App\Http\Responses\ApiResponse`** | Canonical envelope helper: `{success, data}` / `{success:false, error:{code, message}}` |
| **`App\Http\Middleware\ForceJsonResponse`** *(coming Step 5)* | Prepends `Accept: application/json` on `/api/*` |
| **`App\Exceptions\ApiExceptionHandler`** *(coming Step 4)* | Maps all exceptions to envelope shape with stable error codes |
| **Scribe** *(coming Step 8)* | Auto-generates OpenAPI 3.1 + Postman + HTML docs from Laravel code |
| **Kreait Firebase** *(coming Week 4)* | FCM push notification dispatch |

**Auth endpoints:** `/auth/register/step-{1-5}`, `/auth/otp/{phone|email}/{send|verify}`, `/auth/login/{password|phone-otp|email-otp}`, `/auth/password/{forgot|reset}`, `/auth/me`, `/auth/logout`

## File Storage & Media

- **Multi-driver photo storage** ***[added April 22, 2026]*** — configurable via SiteSetting `active_storage_driver`:
  - `public` — Local disk (`storage/app/public/`)
  - `cloudinary` — Cloudinary signed URLs + CDN
  - `r2` — Cloudflare R2 (S3-compatible, via `league/flysystem-aws-s3-v3`)
  - `s3` — AWS S3
- **Hybrid mode** — existing photos stay on original driver (`storage_driver` column on `profile_photos`), new uploads use active driver
- **Intervention Image 4** — Image processing (resize, crop, watermark)
- **WebP output** — 25–35% smaller than JPEG; all new uploads converted
- **Size variants** — thumbnail (150×150), medium (400×400), full (800×1200 max), original preserved
- **Cropper.js** — Client-side crop UI before upload
- **WatermarkService** — GD library, diagonal repeating text watermark
- **endroid/qr-code 6** — QR code generation for share cards + affiliate links
- **Photo privacy** — 3 settings: `gated_premium`, `show_watermark`, `blur_non_premium`
- **Photo Access Grants** *(Phase 2a Week 3)* — per-viewer grants table for approved photo requests

## Payment Gateway

- **Razorpay** — Payment processing (Indian payments)
- **Razorpay PHP SDK** *(added Phase 2a Week 4)* — `razorpay/razorpay` for server-side order creation + signature verification
- **Razorpay Flutter SDK** *(Phase 2b Week 10)* — `razorpay_flutter` 1.4.4 for native in-app checkout
- **Subscription model** — Two tables:
  - `subscriptions` — Razorpay audit trail, amount in paise, coupon tracking
  - `user_memberships` — Feature access, `is_active` + `ends_at`
- **Plans** — Free, Silver, Gold, Diamond, Diamond Plus (admin-editable)
- **Coupons** ***[added April 16, 2026]*** — flat INR or %, plan restrictions, per-user usage tracking, 100% discount short-circuit
- **Webhook handler** *(Phase 2a Week 4)* — `POST /api/v1/webhooks/razorpay` for async `payment.captured` / `payment.failed` / `refund.processed`

## Email & Notifications

- **Laravel Mail** — Email sending via SMTP (Hostinger `smtp.hostinger.com:465` SSL, or admin-configurable)
- **DatabaseMailable pattern** — All Mailables read subject/body from `email_templates` table with Blade fallback
- **Auto-theming** ***[added April 22, 2026]*** — brand colors + logo injected into email templates
- **Notification templates** — `notification_templates` table with slug-based lookup
- **In-app Notifications** — `notifications` table, bell icon + full page
- **Unsubscribe flow** — signed URL per preference key
- **Engagement emails**:
  - Re-engagement (7/14/30-day inactive) — `engagement:send-reengagement` daily 9 AM
  - Weekly matches — `engagement:send-weekly-matches` Sunday 10 AM
  - Profile nudges — `engagement:send-profile-nudges` daily 7 PM
- **Push notifications (mobile)** *(Phase 2a Week 4)* — Firebase Cloud Messaging (FCM) via `kreait/laravel-firebase`

## Search & Matching

- **MySQL queries** — Standard Eloquent `where`/`whereIn` (no external search engine yet)
- **ProfileQueryFilters trait** — Shared base query with `->approved()` scope, privacy filters, religion/denomination/mother-tongue preference matching
- **Search ranking** — 3-tier ordering: Diamond (highlighted) → Premium → Recently Active → Newest
- **Quick Search** — Filter by religion, caste, denomination, age range, location
- **Advanced Search** — 15+ filters (education, income, marital status, height, complexion, etc.)
- **Discover pages** — 13 categories (NRI, Catholic, Karnataka, etc.), 3-level browsing
- **Sort options** — Relevance, Newest First, Recently Active, Age (Low/High), Match Score
- **MatchingService** — 12-criteria weighted scoring: religion(15), age(15), denomination(10), mother_tongue(10), education(10), occupation(10), height(8), native_location(8), working_location(5), marital_status(5), diet(2), family_status(2), horoscope(0)
- **Horoscope matching** ***[added April 16, 2026]*** — 27 Nakshatra compatibility matrix, admin-editable, opt-in via weight > 0
- **Admin match weights** — tunable from Admin → Settings → Match Weights

## Chat & Real-time

- **Interest Messaging** — DB-stored chat between matched profiles (`interest_replies` table, premium-only)
- **Polling (mobile v1)** — 10-second poll via `GET /api/v1/interests/{id}/messages/since/{messageId?}`
- **Real-time (Phase 3)** — Laravel Reverb WebSocket planned once on a VPS (Hostinger shared can't run WebSocket daemons)

## Admin Panel (Filament 5.4.3)

100% complete as of April 23, 2026. See `docs/admin-panel/` for the 15-doc spec.

- **Dashboard** — 10 widgets (stats, charts, tables) with lazy loading + 5-min caching
- **User Management** — Card list, 9 tabs, 14 filters, tabbed view + sectioned edit
- **Verification** — Photo Approvals, ID Verification, Horoscope/Baptism with auto-approve toggles
- **Membership & Payments** — Plans CRUD, Payment History, Memberships, Coupons
- **Site Settings** — General, Theme & Branding, Homepage Content, SEO
- **Content Management** — Communities, FAQs, Success Stories, Email Templates, Notification Templates, Static Pages, Reference Data, Advertisements
- **Moderation** — Suspend/Ban/Unsuspend, Contact Inbox
- **Reports & Analytics** — User/Engagement/Revenue reports with charts + CSV export
- **System** — Match Weights, Horoscope Matching, System Health, Email/SMS/Payment Gateway settings, Activity Log, Database Backup
- **Staff/Telecaller Module** ***[Phase 1.3+1.3.5]*** — Staff roles, lead management, call logs, register-on-behalf, targets & incentives
- **Franchise/Branch Module** ***[Phase 1.4]*** — Branch CRUD, affiliate links + QR codes, branch-scoped access, commission tracking

## Theme & Branding ***[Phase 2.6]***

- **8 preset color palettes** + custom color picker override
- **10 curated Google Fonts** (5 heading + 5 body) + custom font name input
- **Email auto-theming** — brand colors + logo injected automatically
- **3 homepage templates** — Classic / Modern / Premium (admin picks via radio)

## SEO

- Dynamic meta tags (title, description, canonical, Open Graph, Twitter Cards)
- JSON-LD structured data (Organization + WebSite)
- Dynamic `/sitemap.xml` with 30+ URLs, admin toggle
- `robots.txt` with Disallow for private routes
- `.htaccess` — HTTPS redirect, www→non-www, GZIP, 1-month static asset cache

## Mobile App (Flutter) — Phase 2b ***[in planning]***

Full plan at [`docs/mobile-app/`](mobile-app/README.md). Summary stack:

- **Framework:** Flutter 3.41.5, Dart 3.9.x (pinned via FVM)
- **State:** `flutter_riverpod` 3.3.1 (with native offline persistence + auto-retry)
- **HTTP:** `dio` 5.9.2 + `dio_cache_interceptor`
- **Routing:** `go_router` 17.2.2
- **Storage:** `flutter_secure_storage` 9.2.4 + `hive_ce` 2.19.3 (**NOT** `hive` — unmaintained)
- **Firebase:** `firebase_core` 3.12 + `firebase_messaging` 16.2 + `flutter_local_notifications` 18.0
- **Payments:** `razorpay_flutter` 1.4.4 (native SDK)
- **Images:** `cached_network_image` 3.4 + `image_picker` 1.1 + `image_cropper` 12.2.1 + `flutter_image_compress` 2.4
- **Biometric:** `local_auth` 2.3
- **Deep links:** `app_links` 6.3 + Android App Links (native, not Firebase Dynamic Links)
- **Linting:** `very_good_analysis` 7.x (stricter than default `flutter_lints`)
- **Testing:** `flutter_test` + `integration_test` + `mocktail`

**Android target:** SDK 36 (Android 16) from day 1 — ahead of Google Play's Aug 2026 deadline.
**Android min:** SDK 21 (Android 5.0, 99%+ device coverage).
**Package name:** `com.books.KudlaMatrimony` (matches existing webview → enables in-place Play Store update).

## Testing

- **Pest v4.6** ***[added April 23, 2026]*** — Elegant testing framework built on PHPUnit
- **Pest plugin Laravel 4.1** — Laravel-specific expectations + helpers
- **PHPUnit 12.5.12** — Underlying framework, still available for legacy tests
- **Mockery 1.6** — Test doubles
- **Faker 1.23** — Test data generation
- **Contract tests** — `tests/Feature/Api/V1/EnvelopeShapeTest.php` pins the API response shape
- **Test DB:** SQLite `:memory:` by default. For MySQL-specific features (FULLTEXT) we'll set up a dedicated MySQL test DB in Phase 2a Week 2

## Build Tools

- **Vite 8.0** — Asset bundling (CSS + JS)
- **`laravel-vite-plugin` 3.0** — Laravel integration
- **`@tailwindcss/vite`** — Tailwind CSS Vite plugin
- **`@tailwindcss/forms`** — Form styling plugin
- **npm** — Package manager (could move to pnpm; not a priority)

## Hosting & Deployment

- **Hostinger Business** — Shared hosting (live at kudlamatrimony.com)
- **LiteSpeed** — Web server
- **SSH** — active (port 65002); used for deploys + artisan commands
- **Cron Jobs** — Hostinger hPanel; currently running `membership:expiry-reminders` daily
- **`deploy-build.ps1`** — .NET ZipArchive-based build script (Linux-compatible forward slashes)
- **Queue worker (Phase 2a Week 4)** — cron-based: `* * * * * php artisan queue:work --stop-when-empty --max-time=55` (handles async push dispatch + emails)
- **White-label** — single codebase serves any matrimony domain via `SiteSetting`

## PHP Packages (composer.json — current state)

### Production dependencies

| Package | Version | Purpose | Added |
|---------|---------|---------|-------|
| `laravel/framework` | ^13.0 | Core framework | Phase 1 |
| `laravel/tinker` | ^3.0 | REPL for debugging | Phase 1 |
| `laravel/sanctum` | ^4.3 | **API token auth for mobile** | **Phase 2a wk-1 (Apr 23)** |
| `filament/filament` | ^5.4 | Admin panel | Phase 1 |
| `livewire/livewire` | ^4.2 | Dynamic UI components | Phase 1 |
| `spatie/laravel-permission` | ^6.25 | Role-based access control | Phase 1 |
| `intervention/image` | ^4.0 | Image processing (resize, crop, WebP) | Phase 2.5 (Apr 19) |
| `league/flysystem-aws-s3-v3` | ^3.0 | S3 / Cloudflare R2 photo storage driver | Phase 2.6 (Apr 22) |
| `endroid/qr-code` | ^6.0 | QR code generation (affiliate links, share cards) | Phase 1.4 (Apr 19) |
| `phpoffice/phpspreadsheet` | ^5.6 | Excel generation (admin exports) | Phase 2.6 (Apr 22) |

### Dev dependencies

| Package | Purpose | Added |
|---------|---------|-------|
| `pestphp/pest` ^4.6 | **Testing framework** (Pest v4) | **Phase 2a wk-1 (Apr 23)** |
| `pestphp/pest-plugin-laravel` ^4.1 | **Pest Laravel helpers** | **Phase 2a wk-1 (Apr 23)** |
| `phpunit/phpunit` ^12.5.12 | Underlying test runner | Phase 1 |
| `mockery/mockery` ^1.6 | Test doubles | Phase 1 |
| `fakerphp/faker` ^1.23 | Test data generation | Phase 1 |
| `barryvdh/laravel-debugbar` ^4.2 | Debug toolbar | Phase 1 |
| `laravel/pint` ^1.27 | Code formatter | Phase 1 |
| `laravel/pail` ^1.2.5 | Real-time log viewer | Phase 1 |
| `nunomaduro/collision` ^8.6 | Nicer error reporting in CLI | Phase 1 |

### Coming in Phase 2a Weeks 1–4

| Package | Purpose | Planned |
|---------|---------|---------|
| `knuckleswtf/scribe` | API docs generator (OpenAPI + Postman + HTML) | Step 8 |
| `kreait/laravel-firebase` | FCM push dispatch | Week 4 |
| `razorpay/razorpay` | Razorpay PHP SDK (order creation + signature verification) | Week 4 |

## npm Packages (package.json — current state)

| Package | Version | Purpose |
|---------|---------|---------|
| `tailwindcss` | ^4.2.2 | CSS framework |
| `@tailwindcss/forms` | ^0.5.11 | Form element styling |
| `@tailwindcss/vite` | ^4.2.2 | Vite plugin for Tailwind |
| `alpinejs` | ^3.15.10 | JS interactivity (loaded via Livewire) |
| `axios` | ^1.11.0 | HTTP client for AJAX |
| `vite` | ^8.0.0 | Build tool |
| `laravel-vite-plugin` | ^3.0.0 | Laravel Vite integration |
| `concurrently` | ^9.0.1 | Run multiple dev processes |

## Adoption Roadmap

### Already adopted

| What | When | Why |
|------|------|-----|
| ~~Razorpay PHP SDK~~ | Phase 2a wk-4 | Webhook signature verification + order creation |
| ~~Laravel Sanctum~~ | Phase 2a wk-1 | Mobile app auth |
| ~~Pest v4~~ | Phase 2a wk-1 | Cleaner test syntax, parallel execution |
| ~~S3 / Cloudflare R2~~ | Phase 2.6 | Multi-driver photo storage |
| ~~Fast2SMS integration~~ | Phase 1 | Phone OTP (was stub, now live) |
| ~~Cloudinary~~ | Phase 2.5 | Photo CDN (as one of 4 driver options) |

### Should adopt later

| What | When to adopt | Why |
|------|--------------|-----|
| **Meilisearch** | 20K+ profiles (currently 50 live) | Typo-tolerant full-text search, 10× faster than MySQL LIKE |
| **Laravel Reverb** | After VPS migration (Phase 3) | Real-time chat (replaces 10s polling). Hostinger shared can't run WebSocket daemons |
| **Redis** | 500+ concurrent users | Session/cache/queue perf upgrade |
| **Laravel Horizon** | When Redis queues arrive | Queue monitoring dashboard |
| **Apple Developer Program** ($99/yr) | Phase 3 iOS build | Required for App Store |

## Filament 5 Gotchas

| Issue | Solution |
|-------|----------|
| `Section`, `Tabs`, `Grid` not in `Forms\Components` | Use `Filament\Schemas\Components\*` |
| `Tab` not in `Resources\Components` | Use `Filament\Schemas\Components\Tabs\Tab` |
| `Split` layout doesn't exist in schemas | Use `Section` with grid columns |
| `$navigationIcon` type error | Must be `BackedEnum\|string\|null` |
| `$navigationGroup` type error | Must be `\UnitEnum\|string\|null` (not `?string`) |
| RelationManager `$icon` type error | Must be `BackedEnum\|string\|null` |
| Custom Page `$view` property | Must be non-static: `protected string $view` |
| Widget timeout on dashboard | Add `protected static bool $isLazy = true;` |
| Filament assets not loading | Set `inject_assets = true` in Livewire config |
| `SelectFilter` crashes on NULL DB values | Use manual `->options()` with `whereNotNull()` |
| Cache serialization error | Cache final arrays, not Eloquent Collections |

## Mobile App Gotchas (discovered as we build)

See [`docs/mobile-app/reference/troubleshooting.md`](mobile-app/reference/troubleshooting.md) for running list.

Key ones so far:
- **Hive is unmaintained** → use `hive_ce` (Community Edition) instead. We updated pubspec pins before writing any code.
- **Android targetSdk bump to 36 required by Aug 2026** — Play Store deadline. Targeting from day 1 to avoid rebuild.
- **Razorpay SDK crashes on Android 14+ without ProGuard rules** — add `-keep class com.razorpay.** { *; }` early.
- **SQLite `:memory:` test DB can't run MySQL-specific migrations** (FULLTEXT indexes). Solution: MySQL test DB (Phase 2a Week 2).
- **Local dev DB migration drift** — 8 old migrations marked "Pending" but columns exist. Using `--path=` for new migrations meanwhile.

## Architecture Decisions

| Decision | Reason |
|----------|--------|
| Laravel over Next.js | Full-stack PHP is simpler for solo developer; Livewire handles reactivity without React |
| Blade over React for web | Server-rendered, SEO-friendly, no hydration issues |
| Alpine.js over React for web | Lightweight (15KB vs 100KB+), pairs with server-rendered HTML |
| MySQL over PostgreSQL/Supabase | Standard shared-hosting support, no vendor lock-in |
| Multi-driver storage over single Cloudinary | Flexibility for CodeCanyon buyers (each picks their preferred storage) |
| **Flutter over React Native** for mobile | Single codebase Android+iOS, mature ecosystem, Riverpod + Razorpay first-class |
| **Sanctum over Passport** for API auth | Lightweight, personal access token model fits mobile, no OAuth complexity |
| **Thin API controllers over duplicated logic** | API controllers wrap existing `App\Services\*` — zero business-logic duplication |
| **Pest over raw PHPUnit** | Cleaner syntax, parallel by default, architecture tests |
| **Scribe over manual OpenAPI** | Auto-generates from Laravel code + FormRequest rules — zero drift |
| **FCM direct over OneSignal** | Free, no SaaS lock-in, full control |
| **Polling over WebSocket for v1 chat** | Hostinger shared can't run WebSocket daemons; polling unblocks v1. Reverb deferred to Phase 3 after VPS |
| **Razorpay direct over Play Billing** | Matrimony category is allowed to use third-party payments on Play Store |
| **`com.books.KudlaMatrimony` package name kept** | In-place update preserves existing webview install base + reviews |
| White-label via SiteSetting | Single codebase serves multiple matrimony portals (CodeCanyon resale) |

## Version History (major stack changes)

| Date | Change | Why |
|------|--------|-----|
| Apr 23, 2026 | Added Sanctum 4.3, Pest v4.6, mobile app plan (113 docs) | Phase 2a Week 1 kickoff |
| Apr 22, 2026 | Added S3 driver, endroid/qr-code 6, phpoffice/phpspreadsheet 5, intervention/image 4 | Phase 2.6 Theme/Branding + CodeCanyon prep |
| Apr 19, 2026 | Added Cloudflare R2 + Cloudinary drivers, Cropper.js, WebP output | Phase 2.5 multi-driver photo storage |
| Mar 17, 2026 | Upgraded to Laravel 13.2 | Framework major bump |

---

**For mobile-app-specific stack:** [`docs/mobile-app/reference/version-pins.md`](mobile-app/reference/version-pins.md)
**For plan + decisions:** [`docs/mobile-app/00-decisions-and-context.md`](mobile-app/00-decisions-and-context.md)
