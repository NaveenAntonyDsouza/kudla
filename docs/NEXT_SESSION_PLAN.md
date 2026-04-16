# Next Session Plan
**Last Updated:** April 14, 2026

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
- Frontend public pages (app layout) work fine with Tailwind classes

---

## Current Status — ADMIN PANEL 100% COMPLETE

Both sites fully synced and deployed:
- https://anugrahamatrimony.com
- https://kudlamatrimony.com

### Admin Panel Sections (All Complete)

| # | Section | Key Features |
|---|---------|-------------|
| 1 | Dashboard | 10 widgets (stats, charts, recent registrations/payments) |
| 2 | User Management | Card list, 9 tabs, 14 filters, tabbed view, sectioned edit, admin notes, photos, **Admin Create Profile** |
| 3 | Verification | Photo Approvals, ID Verification, Horoscope/Baptism with auto-approve toggles |
| 4 | Membership & Payments | Plans CRUD, Payment History (read-only), Memberships list |
| 5 | Site Settings | General, Theme & Branding (colors + logo), Homepage Content (Why Choose Us repeater), SEO (global + per-page) |
| 6 | Content Management | Communities CRUD, FAQs CRUD, Success Stories CRUD, Email Templates (11 DB-editable), Notification Templates (12 DB-editable) |
| 7 | Interests & Reports | All Interests (tabs), Profile Reports (resolve action), Recommend Matches, Blocked Users, Contact Inbox (reply action), Broadcast Notifications |
| 8 | Moderation | Suspend/Ban/Unsuspend actions on profiles, Contact Inbox with DB persistence |
| 9 | Reports & Analytics | User Reports, Engagement Reports, Revenue Reports — all with charts + CSV export |
| - | Settings | Match Weight Configuration (admin-adjustable algorithm weights) |

### Additional Features Built This Session
- Email OTP Login (3rd login method with admin toggles)
- Sort-by dropdown on search/discover (5 options)
- Last Active badge on profile cards (color-coded)
- Registration validation audit (mandatory/optional consistency)
- Caste dropdown consistency fix (all forms now read from DB `communities` table)
- Tracking codes (GA, GTM, Facebook Pixel) across all 5 layouts

---

## What's Next — Priority Order

### 1. Cloudinary Integration (FIRST PRIORITY)
- Use `cloudinary/cloudinary_php` (core SDK), NOT the Laravel wrapper
- Laravel wrapper has version compatibility issues with Laravel 13.x
- Build a small `CloudinaryService` class (like existing `WatermarkService`)
- Covers: photo upload, CDN delivery, image transformations
- Biggest user-facing performance improvement

### 2. Re-engagement Emails
- Automated emails for inactive users (7d, 14d, 30d)
- Use existing `DatabaseMailable` base class
- Laravel scheduled commands

### 3. Profile Completion Nudges
- In-app notifications for incomplete profiles
- Push users to complete onboarding steps

### 4. Weekly Match Suggestion Emails
- Scheduled email with top 5 matches
- Use MatchingService to calculate

---

## Skipped by Choice (Not Needed Now)

| Item | Reason |
|------|--------|
| SMTP/SMS/Payment admin settings | Kept in .env — works fine |
| Reference Data to DB | config/reference_data.php works fine, only caste was mismatched (now fixed) |
| Real-time notifications (WebSocket) | Not needed for 12-18 months |

---

## Deferred to Phase 2 / CodeCanyon

- VIP/Featured Profiles
- Login History
- Bulk CSV Import
- Meilisearch integration
- Laravel Reverb for real-time
- System (install wizard, license, updater)
- Franchise / Branch Management
- Staff / Telecaller Module
- Advertisement Management
- Wedding Directory

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
13. **Notification templates:** `notification_templates` table with slug-based lookup, same render pattern as email templates
14. **Contact form:** Saves to `contact_submissions` table + sends email to admin
15. **Suspension system:** `profiles` table has `suspension_status` (active/suspended/banned), `suspension_reason`, `suspended_at`, `suspension_ends_at`, `suspended_by`

### Deployment Notes
16. **After fresh upload:** Run `composer install --no-dev --optimize-autoloader && php artisan migrate && ln -s ../storage/app/public public/storage && php artisan config:clear && php artisan view:clear && php artisan cache:clear`
17. **Never upload:** `vendor/`, `node_modules/`, `storage/`, `.env`, `public/storage` (it's a symlink)
18. **Logo/Favicon:** Stored in `storage/app/public/branding/` — if storage is cleared, re-upload from Theme & Branding admin page

### Key File Paths
- Admin panel: `app/Filament/Resources/`, `app/Filament/Pages/`, `app/Filament/Widgets/`
- Email system: `app/Mail/DatabaseMailable.php`, `app/Models/EmailTemplate.php`
- Matching: `app/Services/MatchingService.php` (reads weights from DB)
- Community data: `app/Models/Community.php` (getCasteList, getSubCasteList)
- Reference data: `config/reference_data.php` (heights, weights, education, occupation, etc.)
- Tracking: `resources/views/components/partials/tracking-head.blade.php`, `tracking-body.blade.php`

---

## Razorpay Live

When client gets Razorpay live credentials:
1. Update `.env` on live server with live keys
2. Test with a real payment
3. No code changes needed — just `.env` swap
