# Anugraha Matrimony — Feature Status
**Last Updated:** April 12, 2026

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

## Admin Panel (Filament 5.4.3)

| Feature | Status |
|---------|--------|
| Dashboard: 10 widgets with lazy loading + caching + time-period tabs | Done |
| User Management: Card-style list, 9 tab filters, 14 sidebar filters | Done |
| User Management: Actions (View, Edit, WhatsApp, Approve, Add Note, Activate/Deactivate) | Done |
| User Management: View page (8 tabs covering all profile data + admin notes) | Done |
| User Management: Edit page (9 sections covering all 7 related tables) | Done |
| Admin Notes with follow-up dates + overdue badges | Done |
| ID Verification resource page | Built (pending enhancement) |
| Membership Management resource page | Built (pending enhancement) |
| Site Settings (name, tagline, contact, social links, profile ID prefix) | Done |

## SEO & Performance

| Feature | Status |
|---------|--------|
| Dynamic meta tags (title, description, canonical, OG, Twitter Cards) | Done |
| Structured data (Organization + WebSite JSON-LD) | Done |
| Dynamic XML sitemap (30+ URLs) | Done |
| robots.txt + .htaccess (HTTPS, GZIP, caching, redirects) | Done |
| 301 redirects from old KudlaMatrimony URLs | Done |

## Static Pages

| Feature | Status |
|---------|--------|
| Help/FAQ page (5 categories, 20 questions) | Done |
| Contact Us page (form + email) | Done |
| Privacy Policy + Terms of Service | Done |
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

## Pending Admin Panel Sections

| # | Section | Priority |
|---|---------|----------|
| 3 | Verification (ID Proofs) enhancement | Next |
| 4 | Membership & Payments enhancement | High |
| 5 | Content Management (reference data, pages, templates) | Medium |
| 6 | Interest & Match Management | Medium |
| 7 | Moderation (photos, reports, blocks) | Medium |
| 8 | Reports & Analytics (charts, CSV export) | Medium |
| 9 | System (install wizard, updater) | For CodeCanyon |
| 10 | Franchise / Branch Management | For CodeCanyon |
| 11 | Staff / Telecaller Module | For CodeCanyon |
| 12 | Advertisement Management | For CodeCanyon |
| 13 | Wedding Directory | Phase 2 |

---

## Key Architecture Notes

- **Two subscription tables:** `subscriptions` (Razorpay audit, amount in paise) + `user_memberships` (feature access)
- **Amount in paise:** 99900 = ₹999. Divide by 100 for display.
- **ProfileQueryFilters trait:** Shared base query with ->approved() scope
- **Admin user (id:1):** No profile, redirected to /admin
- **Notifications type:** VARCHAR(50), not ENUM
- **White-label:** Single codebase via SiteSetting serves anugrahamatrimony.com + kudlamatrimony.com
