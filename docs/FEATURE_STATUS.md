# Anugraha Matrimony — Feature Status Report
**Last Updated:** April 2026

---

## All Features — Complete

Every planned feature for this client is now built and deployed.

### Core Platform
- ✅ Registration (5 steps) + Email/Phone OTP verification
- ✅ Login (email/password + OTP) + Forgot Password
- ✅ Onboarding (4 steps: personal, location, preferences, lifestyle)
- ✅ Dashboard (7 sections: CTA, sections, recommended, stats, mutual, views, newly joined, discover)
- ✅ Photo upload & management (profile, album, family) + photo privacy enforcement
- ✅ View & Edit My Profile (9 accordion sections with inline editing)
- ✅ Profile Preview (4 tabbed sections) + Print Profile
- ✅ Public profile view (with privacy on contacts + match score breakdown)

### Search & Discovery
- ✅ Search: Partner Search (15+ filters), Keyword Search, Search by ID
- ✅ Load Partner Preferences on search form
- ✅ Save Search (save, load, delete from search results)
- ✅ Matchmaking Engine: My Matches, Mutual Matches, match score badges, dashboard recommendations
- ✅ Discover Profiles: 13 categories, 3-level browsing (hub → subcategory → results), public for SEO

### Communication & Interaction
- ✅ Interest Send/Accept/Decline with full inbox + chat (inbox optimized: 13→2 queries)
- ✅ Photo Requests: send/approve/ignore with blurred photos + privacy overlays
- ✅ In-app notifications (bell icon dropdown + full page)
- ✅ Email notifications (SMTP configured, working on live)

### Profile Management
- ✅ Shortlist/Favorites (heart icon)
- ✅ Block/Unblock with search exclusion
- ✅ Ignored Profiles with search exclusion
- ✅ Who Viewed My Profile (tracking + views page)
- ✅ Profile Settings (visibility, alerts, hide, delete, password)
- ✅ Profile visibility enforcement (premium only, same religion/denomination/mother tongue)
- ✅ Profile completion calculation (with photo weight)

### Membership & Payments
- ✅ 5 Membership Plans: Free, Silver (₹999/1mo), Gold (₹2999/3mo), Diamond (₹4999/6mo), Diamond Plus (₹7999/12mo)
- ✅ Razorpay payment integration
- ✅ Subscription enforcement: plan-based daily interest limits (5/10/20/50)
- ✅ Contact view restriction (premium or interest-accepted only)
- ✅ Personalized message restriction (paid plans only)
- ✅ Premium badge on profile cards
- ✅ Featured profiles shown first in search

### Admin Panel (Filament 5)
- ✅ Dashboard: 6 stat cards + registration chart + gender chart + recent registrations
- ✅ User Management: list, view, edit (all fields including religious, education, location, contact), activate/deactivate, bulk actions
- ✅ ID Verification: pending queue with badge count, approve/reject with reason
- ✅ Membership Management: view all, manual activate, extend expiry
- ✅ Site Settings: site name, tagline, contact info, homepage stats, social links
- ✅ Admin access control (only role=admin can access /admin)

### Static Pages
- ✅ Help/FAQ page (5 categories, 20 questions, accordion)
- ✅ Contact Us page (form + email to admin + contact sidebar)
- ✅ Privacy Policy + Terms of Service
- ✅ Homepage with community browse + discover links

### Infrastructure
- ✅ Submit ID Proof (front + back upload)
- ✅ Searchable country code dropdown (phone inputs)
- ✅ Mobile responsive + complete hamburger menu with labeled sections
- ✅ Security fixes (gender enforcement, authorization, N+1)
- ✅ Last login tracking
- ✅ Social media links in footer (admin-configurable)
- ✅ Deployed to https://anugrahamatrimony.com (Hostinger)

---

## Pending for CodeCanyon (NOT needed for this client)

| Section | Description |
|---------|-------------|
| Admin: Content Management | Reference data CRUD, static pages editor, email templates |
| Admin: Interest Management | View/manage all interests from admin |
| Admin: Moderation & Support | Contact inbox in admin, reported users |
| Admin: Reports & Analytics | Detailed charts, CSV export |
| Admin: System | Install wizard, license verification, update system |
| Admin: Franchise | Branch management, affiliate links |
| Admin: Staff/Telecaller | Lead management, call logs |
| Admin: Advertisements | Ad spaces, banner management |
| Admin: Wedding Directory | Vendor marketplace (Phase 2) |
| Subscription: Razorpay live | Waiting for client's Razorpay approval |

---

## Important Notes for Next Session

1. **Filament 5 API differences:** Many classes moved from `Forms\Components` and `Tables\Actions` to `Schemas\Components` and `Filament\Actions`. All current code is compatible — but if adding new admin resources, use:
   - `Filament\Schemas\Schema` instead of `Filament\Forms\Form` for form() and infolist()
   - `\Filament\Actions\Action` instead of `Tables\Actions\Action`
   - `BackedEnum|string|null` for `$navigationIcon` type
   - No `Section`, `Tabs`, `Fieldset` from `Forms\Components` — use flat fields or `Schemas\Components`

2. **Notifications type column:** Changed from ENUM to VARCHAR(50) to support new notification types (photo_request, photo_request_approved). Run on live if not done:
   ```sql
   ALTER TABLE notifications MODIFY COLUMN type VARCHAR(50) NOT NULL;
   ```

3. **Two subscription tables exist:**
   - `subscriptions` — Razorpay payment audit trail (keeps payment records)
   - `user_memberships` — Feature access (what `isPremium()` checks)
   - Both are created on payment. Admin panel shows `user_memberships`.

4. **Match score caching:** Currently on-the-fly (fine for <10K users). See `docs/SCALING_GUIDE.md` Section 1 for migration to cached `match_scores` table.

5. **Livewire inject_assets:** Set to `true` in `config/livewire.php`. Required for Filament admin panel. If Alpine.js double-loads on the main site, check this setting.

---

## Key Files
- `docs/SCALING_GUIDE.md` — 8-section optimization guide
- `docs/DEPLOYMENT.md` — Hostinger deployment (12 steps)
- `docs/TECH_STACK.md` — Technology versions
- `docs/MOBILE_APP_PLAN.md` — Flutter app plan (18 screens)
- `docs/admin-panel/` — Admin panel plan (15 sections for CodeCanyon)
- `docs/.env.production` — Production environment template
