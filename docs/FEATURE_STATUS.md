# Anugraha Matrimony — Feature Status Report
**Last Updated:** April 2026

---

## A. In UI But NOT Functional (Users see it but it doesn't work)

| # | Feature | Where It Shows | What's Missing |
|---|---------|---------------|----------------|
| 1 | "To all premium members" visibility option | Settings > Who Can See My Profile | No premium membership enforcement in search |
| 2 | "Only to those who match my Partner Preferences" | Settings > Who Can See My Profile | No matching engine to filter based on partner prefs |
| 3 | Plan features (View Contacts, Interests/Day, etc.) | Membership Plans page | Payment works but features NOT enforced — all users get same access |
| 4 | "Featured Profile" in plans | Plans comparison table | No implementation — premium profiles not highlighted in search |
| 5 | "Priority Support" in plans | Plans comparison table | No support system exists |
| 6 | "Personalized Messages" in plans | Plans comparison table | No restriction — free users can already send custom messages |
| 7 | "Load Partner Preferences" on search | Search page header | Button exists but not connected to saved searches |

## B. Partially Built (Some backend exists but incomplete)

| # | Feature | What Exists | What's Missing |
|---|---------|------------|----------------|
| 8 | Daily interest limit per plan | Hardcoded 5/day for everyone | Should vary by plan (5/15/50) based on subscription |
| 9 | Subscription feature gating | Razorpay payment + subscription DB record | No middleware to check active subscription, no feature limits enforced |
| 10 | Admin review of ID proofs | Upload + "pending" status saved | No admin panel to review/approve/reject |
| 11 | Email notifications | Mail classes + templates ready | SMTP config set to log driver, update .env for real sending |
| 12 | Profile visibility (show_profile_to) | Setting saves to DB | "premium" and "matches" options not enforced in search query |

## C. Backend Model Exists But No UI/Routes (Database ready, feature not accessible)

| # | Feature | Model | What's Needed |
|---|---------|-------|--------------|
| 13 | Photo Requests | PhotoRequest model + migration | Controller, routes, views, notification |
| 14 | Saved Searches | SavedSearch model + migration | Controller, routes, views, load/save UI |
| 15 | Ignored Profiles | IgnoredProfile model + migration | Controller, routes, search exclusion, UI |

## D. Not Built Yet (No code at all)

| # | Feature | Priority | Notes |
|---|---------|----------|-------|
| 16 | Admin Panel | HIGH | Filament installed but no resources. Need: user management, ID proof review, profile approval, stats dashboard |
| 17 | Help/FAQ page | MEDIUM | Static page with common questions |
| 18 | Contact Us page | MEDIUM | Form or info page |
| 19 | Discover Profiles (category browsing) | LOW | NRI, Second Marriage, by State, by Occupation, etc. |
| 20 | Highlighted/Featured Profiles | LOW | Premium profiles shown first in search |
| 21 | Print Profile | LOW | Print-friendly profile view |
| 22 | Saved Searches UI | LOW | Save and load search criteria |
| 23 | Mobile hamburger menu (full dropdown items) | MEDIUM | Mobile nav may not have all dropdown sub-items |

## E. Performance Issues

| # | Issue | Status | Notes |
|---|-------|--------|-------|
| 24 | Shortlist query on every profile card | FIXED | Now uses single cached query |
| 25 | Notification count on every page | OK | Uses lightweight count query |
| 26 | Interest inbox counts (13+ queries) | KNOWN | Could optimize to single aggregate query |

## F. Completed Features

- Registration (5 steps) + Email/Phone OTP verification
- Login (email/password + OTP) + Forgot Password
- Onboarding (4 steps: personal, location, preferences, lifestyle)
- Dashboard with profile cards + real stats (interests, views)
- Photo upload & management (profile, album, family)
- View & Edit My Profile (9 accordion sections with inline editing)
- Profile Preview (4 tabbed sections)
- Public profile view (with privacy on contacts)
- Search: Partner Search (15+ filters), Keyword Search, Search by ID
- Interest Send/Accept/Decline with full inbox + chat
- In-app notifications (bell icon dropdown + full page)
- Email notification templates (ready for SMTP)
- Shortlist/Favorites (heart icon)
- Block/Unblock with search exclusion
- Profile Settings (visibility, alerts, hide, delete, password)
- Who Viewed My Profile (tracking + views page)
- Submit ID Proof (front + back upload)
- Membership Plans (3 plans + Razorpay payment integration)
- Searchable country code dropdown (phone inputs)
- Privacy Policy + Terms of Service pages
- Mobile responsiveness fixes
- Security fixes (authorization, null checks, route validation)
- Profile completion calculation (with photo weight)

---

## Launch Checklist

- [x] Core registration + login flow
- [x] Profile creation + editing
- [x] Photo upload
- [x] Search (partner + keyword + ID)
- [x] Interest send/accept/decline + chat
- [x] Notifications
- [x] Shortlist + Block
- [x] Settings
- [x] Privacy Policy + Terms
- [x] Forgot Password
- [x] Payment integration (Razorpay)
- [x] ID Proof upload
- [x] Configure SMTP email (.env) — working (email OTP functional)
- [x] Matchmaking Engine (My Matches, Mutual Matches, match score badges)
- [x] Discover Profiles (13 categories, 3-level browsing)
- [x] Dashboard redesign (7 widget sections matching Chavara reference)
- [x] Load Partner Preferences on search
- [x] Mobile hamburger menu (complete with labeled sections)
- [x] Homepage community buttons linked to discover routes
- [x] Discover pages public (SEO — no login required)
- [ ] Admin Panel (Filament)
- [ ] Subscription feature enforcement
