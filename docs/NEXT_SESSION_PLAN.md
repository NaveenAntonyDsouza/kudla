# Next Session Plan
**Last Updated:** April 2026

---

## Priority 1: Matchmaking Engine
Build `MatchingService` with weighted scoring algorithm.

### Pages to build:
- **My Matches** (`/matches`) — profiles matching my partner preferences, sorted by match score
- **Mutual Matches** (`/matches/mutual`) — profiles where BOTH sides match each other's preferences

### Match Score Weights:
| Criteria | Weight |
|----------|--------|
| Age range | 15% |
| Religion | 15% |
| Denomination/Caste | 10% |
| Education | 10% |
| Occupation | 10% |
| Mother Tongue | 10% |
| Height range | 10% |
| Location (native) | 10% |
| Marital Status | 5% |
| Family Status | 5% |

### Integration points:
- Dashboard "Recommended Matches" section
- "Only show to those who match my Partner Preferences" setting enforcement
- Matches nav menu dropdown
- Future: daily email "X new matches today"

---

## Priority 2: Discover Profiles (Category Browsing)
Pre-filtered search pages:
- Karnataka Matrimony (native_state = Karnataka)
- Catholic Matrimony (religion = Christian, denomination = Catholic variants)
- NRI Matrimony (residing_country != India)
- Second Marriage (marital_status != Unmarried)
- By Occupation, Mother Tongue, Community

---

## Priority 3: Admin Panel (Filament)
- User management (list, edit, deactivate)
- ID Proof review (approve/reject with reason)
- Profile approval queue
- Stats dashboard (registrations, interests, payments)
- Subscription management

---

## Priority 4: Subscription Feature Enforcement
- Plan-based daily interest limits (5/15/50)
- Contact view limits per plan
- Middleware to check active subscription
- Feature gates in controllers

---

## Completed This Session (for reference):
- ✅ Registration (5 steps) + Login + Forgot Password
- ✅ Onboarding (4 steps)
- ✅ Dashboard with real stats + opposite-gender cards
- ✅ Photo management (profile, album, family)
- ✅ Profile View/Edit (9 sections) + Preview (4 tabs)
- ✅ Search (Partner + Keyword + ID) with 15+ filters
- ✅ Interest Send/Accept/Decline + Chat
- ✅ In-app Notifications (bell icon dropdown)
- ✅ Email notifications (SMTP configured for Hostinger)
- ✅ Shortlist/Favorites (heart icon)
- ✅ Block/Unblock with search exclusion
- ✅ Profile Settings (visibility, alerts, hide, delete, password)
- ✅ Who Viewed My Profile
- ✅ Submit ID Proof (front + back)
- ✅ Membership Plans + Razorpay payment
- ✅ Privacy Policy + Terms pages
- ✅ Phone input with searchable country code + flags
- ✅ Mobile responsiveness fixes
- ✅ Security fixes (gender enforcement, authorization, N+1)
- ✅ Profile completion calculation (with photo weight)
- ✅ Profile visibility preferences (religion, denomination, mother tongue)
- ✅ Last login tracking
- ✅ Deployed to https://anugrahamatrimony.com (Hostinger)

---

## Quick Fixes Still Remaining:
- Mobile hamburger menu needs all dropdown items
- "Load Partner Preferences" button on search not functional
- Keyword search should exclude blocked profiles (uses baseQuery, so already done)

---

## Key Files for Reference:
- `docs/FEATURE_STATUS.md` — complete feature audit
- `docs/DEPLOYMENT.md` — Hostinger deployment guide
- `docs/TECH_STACK.md` — technology versions
- `docs/.env.production` — production environment template
- `docs/admin-panel/` — admin panel plan (16 sections)
