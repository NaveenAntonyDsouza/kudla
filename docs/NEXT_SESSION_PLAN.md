# Next Session Plan
**Last Updated:** April 23, 2026 (post-deploy)
**Live:** kudlamatrimony.com — fully deployed with all April 17-23 work

---

## Tech Stack

| Technology | Version | Notes |
|-----------|---------|-------|
| PHP | 8.3.30 | |
| Laravel | 13.2.0 | |
| Filament | 5.4.3 | Admin panel. **Critical namespace changes — see gotchas below** |
| Livewire | 4.2.3 | `inject_assets = true` required in config/livewire.php |
| Tailwind CSS | 4.2.2 | **v4 uses CSS-first config, NOT tailwind.config.js**. Purges unused classes aggressively. Use inline styles in Filament admin Blade views. |
| Alpine.js | 3.15.10 | Used in all frontend forms |
| Vite | 8.0.0 | Build tool, `npm run build` for production |
| MySQL | 8.x | Hostinger shared hosting |
| Spatie Roles | 6.x | Role-based access (Super Admin, Admin, User) |
| Razorpay | via API | Payments, amount stored in paise |
| Hosting | Hostinger | `exec()` disabled — use `ln -s` for symlinks, not `php artisan storage:link` |

### Tailwind CSS 4 Notes
- No `tailwind.config.js` — config is in CSS file (`@theme` directive)
- Classes in Filament admin Blade views get **purged** because Filament has its own CSS pipeline
- Always use **inline styles** for custom HTML in Filament page views (not Tailwind classes like `bg-red-600`)
- **CRITICAL:** Responsive classes like `md:flex-row`, `md:w-[380px]`, `hidden md:block` do NOT work in production because CSS isn't rebuilt on the server. The homepage uses a `<style>` block with media queries as a workaround. Running `npm run build` on server (or locally then uploading `public/build/`) would fix this.
- Frontend public pages (app layout) work fine with Tailwind classes that existed at last build time

---

## Current Status — ADMIN PANEL 100% COMPLETE + PHASES 1.3, 1.3.5, 1.4, 2.5, 2.6 COMPLETE

Live deployment: configurable via SiteSettings (white-label, any domain)

### Admin Panel Sections (All Complete)

| # | Section | Key Features |
|---|---------|-------------|
| 1 | Dashboard | 10 widgets (stats, charts, recent registrations/payments) |
| 2 | User Management | Card list, 9 tabs, 14 filters, tabbed view, sectioned edit, admin notes, photos, Admin Create Profile |
| 3 | Verification | Photo Approvals, ID Verification, Horoscope/Baptism with auto-approve toggles |
| 4 | Membership & Payments | Plans CRUD, Payment History, Memberships, **Discount Coupons** |
| 5 | Site Settings | General, Theme & Branding, Homepage Content, SEO (with sitemap toggle) |
| 6 | Content Management | Communities, FAQs, Success Stories, Email Templates, Notification Templates, **Static Pages**, **Reference Data**, **Advertisements** |
| 7 | Interests & Reports | All Interests, Profile Reports, Recommend Matches, Blocked Users, Contact Inbox (with **Canned Responses**), Broadcast Notifications |
| 8 | Moderation | Suspend/Ban/Unsuspend actions, Contact Inbox with DB persistence |
| 9 | Reports & Analytics | User Reports, Engagement Reports, Revenue Reports — all with charts + CSV export |
| 10 | Settings | Match Weights, **Horoscope Matching** (Nakshatra compatibility), **System Health**, **Email/SMS/Payment Gateway**, **Activity Log**, **Database Backup** |

### Build History

**Previous Sessions:** Email OTP Login, Sort-by dropdown, Last Active badge, Registration validation, Caste consistency fix, Tracking codes

**April 16 Morning:** Bug fixes, Search dropdown, Public search pages, Homepage enhancements (counters, FAQ, testimonials, app CTA), Hero image, Google Map, PostHog, Mobile responsiveness, UI polish

**April 16 Evening — Admin Panel Completion:**
- Static Pages Editor (WYSIWYG, DB-backed, variable substitution, footer auto-populates)
- System Health & Maintenance (PHP/MySQL info, cache buttons, error log viewer)
- Coupon/Discount System (full CRUD, frontend AJAX apply, 100% discount handling)
- Email/SMS/Payment Gateway Settings (SMTP, Fast2SMS, Razorpay from admin, test email button)
- Reference Data Editor (26 categories, grouped/simple lists, DB override with config fallback)

**April 16 Night — Final Admin Panel Features:**
- Sitemap admin toggle (SEO Settings)
- Activity Log Viewer (read-only resource + LogsAdminActivity trait on key actions)
- Canned Responses for Contact Inbox (JSON in site_settings, select dropdown in reply modal)
- Database Backup (pure PHP, no exec(), StreamedResponse, works on shared hosting)
- Horoscope Compatibility (27 Nakshatra matrix, admin-editable, integrated into MatchingService)
- Advertisement Management (CRUD, 5 ad slots, image/HTML/AdSense, click tracking, CTR stats)

**April 17-18 — Staff Module (Phase 1.3 + 1.3.5):**
- Staff roles + staff CRUD + login with restricted permissions
- Lead Management (CRUD, status flow, assigned staff)
- Call Logs (linked to leads/profiles, duration, outcome, follow-up)
- Register on Behalf (walk-in user registration by staff)
- Staff personal dashboard + performance widgets
- Staff Targets & Incentives (monthly targets, auto-computed actuals, live incentive calculation, Copy-to-Next-Month bulk action)

**April 19 — Franchise/Branch Module (Phase 1.4) + Re-engagement (Quick Wins):**
- Branch CRUD + affiliate links + QR codes + short URL tracking
- BranchScopable trait applied to Profile, Lead, Coupon, StaffTarget, BranchPayout
- Branch Manager role with scoped access
- Branch dashboard widgets (overview, leads funnel, revenue chart, staff performance, affiliate stats)
- Commission tracking + BranchPayout model
- Re-engagement emails (7/14/30-day inactive), Weekly match emails (Sunday 10 AM), Profile nudges (daily 7 PM)
- Multi-driver photo storage (Local / Cloudinary / R2), hybrid mode, WebP output, Cropper.js
- Profile completion nudge dashboard + Weekly matches dashboard + Reengagement dashboard

**April 22 — Theme/Branding (Phase 2.6) + Brand Neutralization + CodeCanyon Prep:**
- Site identity polish: LinkedIn social link, click-to-WhatsApp, announcement banner, stats auto-compute toggle
- 8 preset color palettes + custom color picker override
- Email template auto-theming (brand colors + logo injected into all emails)
- 10 curated Google Fonts (5 heading + 5 body) + custom Google Font name input
- Codebase brand neutralization (removed Anugraha + Kudla, replaced with MatrimonyTheme placeholder)
- AWS S3 added as 4th photo storage driver (joins Local / Cloudinary / R2)
- `league/flysystem-aws-s3-v3` installed (fixed latent bug — R2 was silently broken)
- `php artisan matrimony:demo-seed` + `matrimony:demo-clean` commands (50 profiles w/ avatars, 30 leads, 100 call logs, 15 subs, 6 targets, 20 interests, 30 views, 3 testimonials — all tagged for safe removal)

**April 23 — Homepage Templates + Deploy Prep:**
- 3 homepage design templates (Classic / Modern / Premium) — same content/data layer, different visual layouts, admin picks via radio in Settings → Homepage Content
- Classic = preserves current 11-section layout (hero + form side-by-side, counters, carousel testimonials, community browse, FAQ, app CTA)
- Modern = tech-startup aesthetic (split hero with profile card stack, compact 3-field registration, horizontal stats bar, embedded search, card grids, bottom CTA strip)
- Premium = editorial/magazine aesthetic (cinematic full-bleed hero with CTA button, narrative stats, masonry "Editor's Picks" with blur, long-form success stories, visual community tiles, serif headlines throughout)
- HomeController selects template from `homepage_template` SiteSetting (fallback: classic)
- DEPLOY_CHECKLIST.md written (27-step runbook for pending deploy of all accumulated changes since April 17)
- `npm run build` completed (new CSS hash `app-4erY1nPZ.css`, 92KB — all recent Tailwind classes included)

**April 23 — DEPLOYED to kudlamatrimony.com (live):**
- Pre-flight SQL ran (engagement emails disabled, migration rename, audit clean)
- 3.77 MB ZIP built via `deploy-build.ps1` (.NET ZipArchive — Linux-compatible forward slashes)
- All 19 new migrations ran cleanly on live (51 users / 50 profiles / 5 subs preserved, branch-scoped to Head Office)
- 15 new composer packages installed (aws-sdk-php, flysystem-aws-s3-v3, intervention/image v4, endroid/qr-code, phpoffice/phpspreadsheet)
- 3 pre-existing bugs found + fixed during smoke test:
  - Carousel `<div>` missing closing `>` (success stories carousel didn't auto-rotate)
  - `diffInDays()` returns float in Carbon v3 → "2.5020910621759d ago" → cast to `(int)`
  - Step 5 `creator_contact_number` field missed `:required="true"` (no red asterisk)
- `config/discover.php` refactored: 14 closures → `App\Services\DiscoverConfigService`. `config:cache` now works on production.
- Engagement emails (re-engagement, weekly_matches, profile_nudges) staying disabled until gradual re-enable over Apr 24-28

**April 23 — POST-DEPLOY: git setup + CSS rebuild + hotfix:**
- GitHub repo created at https://github.com/NaveenAntonyDsouza/kudla
- 273 uncommitted files committed in 5 logical chunks (admin panel, staff, franchise, photo+reengagement, theme/deploy)
- Branch renamed master → main; tag `deploy-2026-04-23` points to chunk 5 (= live state)
- `npm run build` regenerated CSS (new hash `app-Bmgt2YRP.css`, 97.5 KB, +5 KB vs previous) — uploaded to live
- **Hotfix required**: 20 min after deploy, homepage crashed with `Undefined array key "subcategories"` at `home/classic.blade.php:512`. Root cause: the discover refactor changed the config shape, and I updated the controller's `resolveSubcategories()` helper but missed an inline `@php` block in the Blade that did its own subcategory resolution. Fixed both `home/classic.blade.php` and orphan `home.blade.php` to handle the new `subcategories_source` key. Recovery via tiny hotfix ZIP + `php artisan view:clear` on live. ~5 minute downtime.
- Hotfix commit: `054c9a6` on main
- **DEPLOY_CHECKLIST.md updated** with 4 new lessons from today (grep-all-consumers for refactors, npm run build as mandatory step 1, hotfix-ZIP pattern, Network-tab CSS hash verification)
- **Git hygiene cleanup**: untracked legacy `public/build/manifest.json` + `public/build/assets/app-BU6mFzGd.js` so they stop showing up as modified after every `npm run build`. Files remain on disk; `.gitignore` now fully consistent with tracking.
- Final state: zero post-deploy errors, site working cleanly, 8 commits on `main`, working tree clean, tag `deploy-2026-04-23` preserved

---

## What's Next — Priority Order

### 0. Quick Wins (DONE ✓)
- ~~Re-engagement Emails~~ ✓ — `engagement:send-reengagement` daily 9 AM, 7/14/30-day, master switch + unsubscribe
- ~~Profile Completion Nudges~~ ✓ — `engagement:send-profile-nudges` daily 7 PM, configurable threshold
- ~~Weekly Match Suggestion Emails~~ ✓ — `engagement:send-weekly-matches` Sunday 10 AM, configurable count
- ~~Cloudinary Integration~~ ✓ — Part of Phase 2.5 multi-driver photo storage
- ~~AWS S3 as 4th storage driver~~ ✓ — Done April 22 (also fixed latent R2 dependency bug)
- ~~Demo data seeder~~ ✓ — Done April 22 (`matrimony:demo-seed` + `matrimony:demo-clean`)
- **Run `npm run build`** locally, upload `public/build/` to live — Still pending, fixes Tailwind responsive classes on prod

---

### PHASE 1: First Priority (For Live Business + Hiring Staff) — DONE ✓

#### 1. VIP / Featured Profiles ✓
- ~~`is_vip` and `is_featured` columns on `profiles` table~~ — Done (April 16)
- ~~Admin marks profiles as VIP or Featured~~, ~~badges~~, ~~boosted ranking~~ — Done

#### 2. Login History ✓
- ~~`login_history` table with IP, user agent, device, browser, location~~ — Done (April 16)
- ~~Middleware logs every successful login~~, ~~admin tab~~, ~~Filament resource~~ — Done

#### 3. Staff / Telecaller Module ✓ (April 17-18)
- ~~3a. Staff Management~~ — Done
- ~~3b. Lead Management~~ — Done
- ~~3c. Call Log~~ — Done
- ~~3d. Register on Behalf~~ — Done
- ~~3e. Telecaller Dashboard & Performance~~ — Done
- ~~3f. Staff Targets & Incentives (Phase 1.3.5)~~ — Done

#### 4. Franchise / Branch Management ✓ (April 19)
- ~~4a. Branch CRUD~~ — Done
- ~~4b. Branch Assignment (auto via ?ref=CODE)~~ — Done
- ~~4c. Franchise Affiliate Links + QR codes~~ — Done
- ~~4d. Branch Dashboard (5 widgets, scoped access)~~ — Done
- ~~4e. Branch Revenue & Commission + BranchPayout CRUD~~ — Done

---

### PHASE 2: Native Mobile App (Flutter) — CURRENT FOCUS

**Context:**
- Existing webview app on Play Store: `com.books.KudlaMatrimony` (kudlamatrimony.com wrapper)
- Goal: upgrade from webview → Flutter native app for own business first, then bundle in CodeCanyon listing as v2.0 upsell
- Full spec already written: see [MOBILE_APP_PLAN.md](MOBILE_APP_PLAN.md)

**Why app before CodeCanyon packaging:**
- Webview app is live but limited (no push, no biometric, no native UX)
- Flutter gives Android + iOS from one codebase
- Once native app is shipped, CodeCanyon listing can include "Web + Flutter App" as a premium tier (commands higher price)

**Build Order (from MOBILE_APP_PLAN.md):**

#### 5a. REST API Layer (prerequisite for app)
- Install Laravel Sanctum for token-based auth
- `api.php` routes returning JSON for all key flows (login, register, profile, search, interests, chat, membership, payments)
- `/api/v1/` prefix for versioning
- Ensure all photo URLs returned are absolute (not relative)
- FCM token storage on users table for push dispatch

#### 5b. Flutter Project Setup
- Flutter SDK + project scaffold
- Auth screens (login with phone/email + OTP, registration 5 steps)
- State management: Riverpod
- HTTP client with Sanctum bearer token interceptor

#### 5c. Core Screens
- Dashboard (matches, new profiles, stats)
- Search (partner preferences, keyword, matri ID)
- Profile View (swipeable tabs for sections)
- My Profile (view + edit)
- Photo Management

#### 5d. Interactions
- Interest Inbox (tabs: received, sent, accepted, declined)
- Chat (after interest accepted) — initially polling, WebSocket later
- Shortlist, Who Viewed, Notifications bell
- Push notifications via FCM

#### 5e. Membership + Payments
- Plans page
- Razorpay Flutter SDK integration
- Receipt + activation

#### 5f. Polish + Publish
- Biometric login for returning users
- Pull-to-refresh on all lists
- Bottom tab nav (Home, Search, Interests, Profile, More)
- Offline caching for viewed profiles
- Share profile card as image
- Play Store + App Store submission

**Estimated effort:** 3-6 months (API layer 1-2 weeks, Flutter build 2-4 months, store review + polish 2-4 weeks)

---

### PHASE 3: CodeCanyon Preparation (after mobile app lands)

#### 6. Bulk CSV Import
- Download CSV template with column headers
- Upload CSV → validate → preview (show errors/warnings) → confirm → import
- Supports: basic profile fields, religious info, education, location
- Assign imported profiles to a branch
- Send registration credentials via SMS/Email to imported users
- Error report downloadable as CSV

#### 7. System — Installation, Updates & Licensing

**7a. Installation Wizard**
- 5-step first-time setup wizard (shown on first `/admin` visit)
- Step 1: Environment check (PHP version, extensions, file permissions)
- Step 2: Database config (host, port, database, username, password + test connection)
- Step 3: Create super admin account
- Step 4: Purchase code activation (Envato API verification)
- Step 5: Quick site settings (name, logo, contact info)
- After completion: redirect to dashboard, wizard never shows again

**7b. Update System (Manual ZIP Upload)**
- Admin page: System > Software Update
- Upload update.zip → verify → auto backup → extract → run migrations → clear caches → show changelog
- Safety: pre-update backup, rollback button, maintenance mode during update
- Version number stored in site_settings

**7c. One-Click Auto-Update (requires separate update server)**
- Customer admin panel checks update server for new versions
- "Update Now" button downloads and installs automatically
- Update server tracks all installations, versions, license status

**7d. Purchase Code Verification**
- Envato API integration: verify purchase code, get buyer info, license type, support dates
- Store license info in site_settings
- Periodic re-verification (optional)

**7e. Version & Changelog**
- Show full version history in admin
- Changelog from `update_manifest.json` inside each update zip

#### 8. Wedding Directory (Separate Module)

**8a. Vendor Categories**
- CRUD for categories: Photography, Catering, Venues, Florists, Makeup, DJ, Cards, Jewellers, etc.
- Icon, sort order, active toggle

**8b. Vendor Registration & Profile**
- Separate registration flow for vendors (not matrimony users)
- Business name, category, owner, phone, email, website, address, city, state
- Description (rich text), logo, portfolio images (up to 10), starting price
- Admin verification badge

**8c. Vendor Dashboard**
- Vendors see: profile views, inquiry count, inquiry list, edit profile/photos

**8d. Browse & Search Vendors (Frontend)**
- `/wedding-directory` — browse by category
- Filter by category + city
- Vendor cards: logo, name, category, city, price, rating
- Vendor detail page with portfolio gallery + inquiry form

**8e. Vendor Inquiry System**
- Logged-in users send inquiries: event date, message, budget
- Vendor receives email + dashboard notification

**8f. Admin Vendor Management**
- Approve/reject vendors, feature vendors on homepage, analytics

---

### PHASE 4: Future Enhancements

#### 9. Multi-language / Localization
- Laravel localization for Hindi, Kannada, Tulu, Malayalam
- Language switcher in header
- All user-facing text translatable
- Admin can edit translations from panel
- Huge effort (~20+ hours) — consider as paid add-on for CodeCanyon

#### 10. Web Push Notifications (Firebase/OneSignal)
- Note: **mobile push** (FCM) is covered by the Flutter app in Phase 2 step 5d
- This item is for WEB-only push (desktop/browser). Lower priority once native app ships
- Browser push for: new interest, interest accepted, profile view, new match
- Admin broadcast push

#### 11. GDPR Compliance Tools
- User data export (download all personal data as ZIP)
- Account deletion request flow (user requests → admin reviews → permanent delete)
- Cookie consent banner with granular controls
- Target market is India, but needed for international CodeCanyon buyers

#### 12. Meilisearch Integration
- Replace MySQL LIKE queries with Meilisearch for instant search
- Typo-tolerant, faceted search
- Significant performance improvement at 10K+ profiles
- Requires Meilisearch server (not available on shared hosting — needs VPS)

#### 13. Real-time Notifications (Laravel Reverb / WebSocket)
- Live notification bell without page refresh
- Real-time interest status updates
- Online/offline indicator on profiles
- Not needed for 12-18 months

---

## Skipped by Choice (Not Building)

| Item | Reason |
|------|--------|
| Special case search (IIT/IIM) | Keyword search covers this |
| Custom code injection (Plugin) | Security risk |
| Staff pending task assignment | Lead management + follow-ups cover this |
| Franchise payout request flow | Commission tracking is enough — payouts handled offline |
| Homepage drag-and-drop builder | Editable fields + toggles is sufficient |
| Custom profile badges | VIP + Featured + Verified covers all cases |

---

## Important Notes for Future Sessions

### Filament 5 Namespace Gotchas (CRITICAL)
1. **Section:** `\Filament\Schemas\Components\Section` (NOT `Filament\Forms\Components\Section`)
2. **Actions:** `\Filament\Actions\EditAction`, `\Filament\Actions\DeleteAction`, `\Filament\Actions\Action`, `\Filament\Actions\BulkAction` (NOT `Tables\Actions\*`)
3. **Form signature:** `public static function form(Schema $form): Schema`
4. **Infolist signature:** `public static function infolist(Schema $infolist): Schema` (NOT `Infolist`)
5. **Admin Blade views:** Use inline styles (NOT Tailwind classes — they get purged by Filament's CSS pipeline)
6. **Tables\Columns and Tables\Filters** still work as-is (NOT moved)

### Database & Architecture Notes
7. **Two subscription tables:** `subscriptions` (Razorpay audit, paise) + `user_memberships` (feature access)
8. **Toggle save fix:** SiteSettings saves toggles as '1'/'0' (not empty string)
9. **Search sort:** Use subqueries instead of JOINs to avoid ambiguous column errors with baseQuery
10. **Caste/Community:** ALL forms now read from `communities` DB table via `Community::getCasteList()`. Config `caste_list` is unused.
11. **Email templates:** All Mailables extend `DatabaseMailable` — reads subject/body from `email_templates` table, falls back to Blade views
12. **Match weights:** Admin-configurable via `match_weights` key in `site_settings`, read by `MatchingService::getWeights()`
13. **Horoscope matching:** Nakshatra compatibility matrix stored in `horoscope_compatibility` key in site_settings. Weight default is 0 (opt-in). Only activates when both users have nakshatra set AND horoscope enabled AND weight > 0.
14. **Notification templates:** `notification_templates` table with slug-based lookup, same render pattern as email templates
15. **Contact form:** Saves to `contact_submissions` table + sends email to admin. Canned responses in `canned_responses` JSON key in site_settings.
16. **Suspension system:** `profiles` table has `suspension_status` (active/suspended/banned), `suspension_reason`, `suspended_at`, `suspension_ends_at`, `suspended_by`
17. **Activity logging:** `LogsAdminActivity` trait with `logActivity($action, $model, $changes)`. Applied to UserResource, SiteSettings, ContactSubmissionResource. Logs stored in `admin_activity_log` table.
18. **Coupon system:** `coupons` + `coupon_usages` tables. Subscription table has `coupon_id`, `coupon_code`, `discount_amount`, `original_amount`. Handles 100% discount (activate without Razorpay).
19. **Static pages:** `static_pages` table with WYSIWYG content, variable substitution ({{ app_name }}, {{ email }}), footer dynamically populated from DB.
20. **Reference data:** `config/reference_data.php` (defaults) → `ReferenceDataService::get()` checks DB override first (stored as `ref_data_*` in site_settings).
21. **Gateway config:** `GatewayConfigProvider` reads SMTP/SMS/Razorpay settings from site_settings and overrides .env values at boot.
22. **Database backup:** Pure PHP backup via PDO (no exec()). StreamedResponse for large DBs. Works on Hostinger shared hosting.
23. **Advertisements:** `advertisements` table with ad spaces (homepage_banner, sidebar, search_results, footer_banner, mobile_banner). Image + HTML/AdSense support. Click tracking via `/ad/click/{id}` route. `<x-ad-slot position="..." />` Blade component.

### Deployment Notes
24. **After fresh upload:** Run `composer install --no-dev --optimize-autoloader && php artisan migrate && ln -s ../storage/app/public public/storage && php artisan config:clear && php artisan view:clear && php artisan cache:clear`
25. **Never upload:** `vendor/`, `node_modules/`, `storage/`, `.env`, `public/storage` (it's a symlink)
26. **Logo/Favicon:** Stored in `storage/app/public/branding/` — if storage is cleared, re-upload from Theme & Branding admin page

### Key File Paths
- Admin panel: `app/Filament/Resources/`, `app/Filament/Pages/`, `app/Filament/Widgets/`
- Email system: `app/Mail/DatabaseMailable.php`, `app/Models/EmailTemplate.php`
- Matching: `app/Services/MatchingService.php` (reads weights from DB, includes horoscope)
- Community data: `app/Models/Community.php` (getCasteList, getSubCasteList)
- Reference data: `config/reference_data.php` (defaults) → `app/Services/ReferenceDataService.php` (reads DB override first)
- Reference data admin: `app/Filament/Pages/ReferenceDataEditor.php`
- Static pages: `app/Models/StaticPage.php`, `app/Filament/Resources/StaticPageResource.php`
- Coupons: `app/Models/Coupon.php`, `app/Models/CouponUsage.php`, `app/Filament/Resources/CouponResource.php`
- Gateway settings: `app/Filament/Pages/GatewaySettings.php`, `app/Providers/GatewayConfigProvider.php`
- System health: `app/Filament/Pages/SystemHealth.php`
- Database backup: `app/Filament/Pages/DatabaseBackup.php`
- Activity log: `app/Traits/LogsAdminActivity.php`, `app/Filament/Resources/ActivityLogResource.php`
- Horoscope config: `app/Filament/Pages/HoroscopeConfig.php`
- Advertisements: `app/Models/Advertisement.php`, `app/Filament/Resources/AdvertisementResource.php`, `resources/views/components/ad-slot.blade.php`
- Tracking: `resources/views/components/partials/tracking-head.blade.php`, `tracking-body.blade.php`

---

## Admin Panel — Things to Configure
- **Hero image:** Admin > Settings > Homepage Content — upload a couple photo (1920x800)
- **Google Map:** Admin > General Settings > Contact Info — paste Google Maps embed URL
- **PostHog:** Admin > SEO Settings > Tracking & Analytics — paste PostHog API key (phc_...)
- **App Store URLs:** Admin > General Settings > Mobile App — replace dummy URLs with real ones
- **Success Story photos:** Admin > Content Management > Success Stories — add couple photos
- **Stats:** Admin > Homepage Content > Stats — update Members/Marriages/Years counts
- **Horoscope weights:** Admin > Settings > Match Weights — set Horoscope weight > 0 to enable for Hindu users
- **Canned responses:** Admin > General Settings > Canned Responses — add reply templates for Contact Inbox

---

## Razorpay Live

When client gets Razorpay live credentials:
1. Update `.env` on live server with live keys (or configure from Admin > Email, SMS & Payment)
2. Test with a real payment
3. No code changes needed
