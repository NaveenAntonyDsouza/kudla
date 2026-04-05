# Anugraha Matrimony — Feature Status Report
**Last Updated:** April 2026

---

## A. In UI But NOT Functional

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 1 | "To all premium members" visibility | **FIXED** | Enforced in baseQuery — non-premium users can't see premium-only profiles |
| 2 | "Only to those who match my Partner Preferences" | **PARTIAL** | Enforced via religion/denomination/mother tongue toggles. Full reverse-matching deferred to v2 |
| 3 | Plan features (View Contacts, Interests/Day) | **PENDING** | Payment works but limits NOT enforced — needs subscription enforcement |
| 4 | "Featured Profile" in plans | **FIXED** | Premium profiles shown first in search + "Premium" badge on cards |
| 5 | "Priority Support" in plans | **FIXED** | Contact Us page + FAQ built |
| 6 | "Personalized Messages" in plans | **PENDING** | No restriction — needs subscription enforcement |
| 7 | "Load Partner Preferences" on search | **FIXED** | Pre-fills search form from saved preferences |

## B. Partially Built

| # | Feature | Status | Notes |
|---|---------|--------|-------|
| 8 | Daily interest limit per plan | **PENDING** | Hardcoded 5/day — needs plan-based limits (5/15/50) |
| 9 | Subscription feature gating | **PENDING** | No middleware to check subscription, no feature limits |
| 10 | Admin review of ID proofs | **PENDING** | Upload works, needs admin panel to review |
| 11 | Email notifications | **FIXED** | SMTP working on live (email OTP functional) |
| 12 | Profile visibility (show_profile_to) | **FIXED** | "premium" and "matches" options enforced in baseQuery |

## C. Backend Model — Now Built

| # | Feature | Status |
|---|---------|--------|
| 13 | Photo Requests | **DONE** — Controller, views, send/approve/ignore, photo privacy enforcement (blur/hidden/request) |
| 14 | Saved Searches | **DONE** — Save from search results, load, delete, summary preview |
| 15 | Ignored Profiles | **DONE** — Toggle ignore, excluded from baseQuery (search, matches, discover) |

## D. Feature Status

| # | Feature | Status |
|---|---------|--------|
| 16 | Admin Panel (Filament) | **PENDING** — 15 sections planned in docs/admin-panel/ |
| 17 | Help/FAQ page | **DONE** — 5 categories, 20 questions, accordion |
| 18 | Contact Us page | **DONE** — Form with email + contact sidebar |
| 19 | Discover Profiles | **DONE** — 13 categories, 3-level browsing, public for SEO |
| 20 | Featured Profiles | **DONE** — Premium profiles first in search + badge |
| 21 | Print Profile | **DONE** — Print-friendly view with @media print CSS |
| 22 | Saved Searches UI | **DONE** — Save, load, delete from search results |
| 23 | Mobile hamburger menu | **DONE** — All items with labeled sections |

## E. Performance

| # | Issue | Status |
|---|-------|--------|
| 24 | Shortlist query N+1 | **FIXED** — static cache |
| 25 | Notification count | **OK** — lightweight query |
| 26 | Interest inbox counts (13 queries) | **FIXED** — optimized to 2 queries |

## F. Completed Features

- Registration (5 steps) + Email/Phone OTP verification
- Login (email/password + OTP) + Forgot Password
- Onboarding (4 steps: personal, location, preferences, lifestyle)
- Dashboard (7 sections: CTA, sections, stats, recommended, mutual, views, newly joined, discover)
- Photo upload & management (profile, album, family) + photo privacy enforcement
- View & Edit My Profile (9 accordion sections with inline editing)
- Profile Preview (4 tabbed sections) + Print Profile
- Public profile view (with privacy on contacts + match score breakdown)
- Search: Partner Search (15+ filters), Keyword Search, Search by ID + Load Partner Preferences + Save Search
- Matchmaking Engine: My Matches, Mutual Matches, match score badges, dashboard recommendations
- Discover Profiles: 13 categories, 3-level browsing (hub → subcategory → results), public for SEO
- Interest Send/Accept/Decline with full inbox + chat (inbox optimized: 13→2 queries)
- Photo Requests: send/approve/ignore with blurred photos + privacy overlays
- In-app notifications (bell icon dropdown + full page)
- Email notifications (SMTP configured, working on live)
- Shortlist/Favorites (heart icon)
- Block/Unblock with search exclusion
- Ignored Profiles with search exclusion
- Saved Searches (save, load, delete)
- Profile Settings (visibility, alerts, hide, delete, password)
- Who Viewed My Profile (tracking + views page)
- Submit ID Proof (front + back upload)
- Membership Plans (3 plans + Razorpay payment) + Premium badge + Featured ordering
- Profile visibility enforcement (premium only, same religion/denomination/mother tongue)
- Help/FAQ page (5 categories, 20 questions)
- Contact Us page (form + email to admin)
- Searchable country code dropdown (phone inputs)
- Privacy Policy + Terms of Service pages
- Mobile responsive + complete hamburger menu
- Security fixes (gender enforcement, authorization, N+1)
- Profile completion calculation (with photo weight)
- Homepage community buttons linked to discover routes
- Last login tracking

---

## Still Pending (2 items)

| # | Feature | Priority | Description |
|---|---------|----------|-------------|
| 1 | **Admin Panel (Filament)** | HIGH | 15 sections: users, approvals, ID proofs, payments, settings, content, reports, franchise, staff |
| 2 | **Subscription Enforcement** | HIGH | Plan-based daily interest limits, contact view limits, personalized message restriction, middleware |

---

## Launch Checklist

- [x] Core registration + login flow
- [x] Profile creation + editing + print
- [x] Photo upload + photo privacy + photo requests
- [x] Search (partner + keyword + ID + load preferences + save search)
- [x] Matchmaking engine (my matches + mutual matches + score breakdown)
- [x] Discover profiles (13 categories)
- [x] Interest send/accept/decline + chat
- [x] Notifications (in-app + email)
- [x] Shortlist + Block + Ignore
- [x] Settings (visibility, alerts, hide, delete, password)
- [x] Privacy Policy + Terms + FAQ + Contact Us
- [x] Forgot Password
- [x] Payment integration (Razorpay)
- [x] ID Proof upload
- [x] SMTP email working
- [x] Featured profiles + premium badge
- [x] Mobile responsive
- [x] Dashboard redesign (7 sections)
- [x] Homepage → discover links
- [ ] **Admin Panel (Filament)**
- [ ] **Subscription feature enforcement**
