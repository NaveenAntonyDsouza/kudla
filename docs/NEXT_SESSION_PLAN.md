# Next Session Plan
**Last Updated:** April 2026

---

## Priority 1: Matchmaking Engine
Build `MatchingService` with weighted scoring algorithm.

### Pages to build:
- **My Matches** (`/matches`) — profiles matching my partner preferences, sorted by match score
- **Mutual Matches** (`/matches/mutual`) — profiles where BOTH sides match each other's preferences

### Match Score Algorithm:

**Scoring logic:**
- Only criteria where the user HAS set a preference are scored
- Unset preferences are skipped (not penalized) — weights redistribute proportionally
- Each criteria scores 0 (no match) or 1 (match)
- Final score = (weighted sum of matched criteria) / (sum of weights for set criteria) x 100%

**Example:** User sets only Age, Religion, Education preferences (total weight = 40%).
Candidate matches Age + Religion but not Education → score = 30/40 = 75%.

**Default Weights (admin-configurable from panel):**

| Criteria | Weight | Match Logic |
|----------|--------|-------------|
| Religion | 15 | Profile religion IN preferred religions |
| Age range | 15 | Profile age BETWEEN age_from AND age_to |
| Denomination/Caste | 10 | Profile denomination/caste IN preferred list |
| Mother Tongue | 10 | Profile mother_tongue IN preferred list |
| Education | 10 | Profile education IN preferred education levels |
| Occupation | 10 | Profile occupation IN preferred occupations |
| Height range | 8 | Profile height BETWEEN height_from AND height_to |
| Location (native) | 8 | Profile native_state IN preferred states OR native_country IN preferred countries |
| Location (working) | 5 | Profile working_country IN preferred working countries |
| Marital Status | 5 | Profile marital_status IN preferred list |
| Diet | 2 | Profile diet IN preferred diet (if set) |
| Family Status | 2 | Profile family_status IN preferred list |

**Hard filters (exclude, don't just score low):**
- Opposite gender (already enforced)
- Blocked profiles (already enforced)
- Hidden/inactive profiles (already enforced)
- Profile visibility preferences (same religion/denomination/mother tongue settings)

**Display:**
- 80%+ = Green badge "Great Match"
- 60-79% = Yellow badge "Good Match"
- 40-59% = Grey badge "Partial Match"
- Below 40% = No badge (still shown unless filtered out)
- Profile view shows detailed breakdown with checkmarks

### Integration points:
- Dashboard "Recommended Matches" section (top 6 by score)
- My Matches page (all matches sorted by score, paginated)
- Mutual Matches page (both sides match each other's preferences)
- "Only show to those who match my Partner Preferences" setting enforcement
- Matches nav menu dropdown
- Admin panel: configurable weights (see docs/admin-panel/07-interests-matching.md)
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
- ✅ Matchmaking Engine (MatchingService, My Matches, Mutual Matches, Dashboard recommendations, Profile view breakdown)
- ✅ Discover Profiles (13 categories, 3-level browsing: hub → subcategory list → results)

---

## Quick Fixes (All Resolved):
- ✅ Mobile hamburger menu — all dropdown items with labeled sections
- ✅ "Load Partner Preferences" button — pre-fills search form from saved preferences
- ✅ Keyword search — already uses baseQuery (blocked profiles excluded)

---

## Key Files for Reference:
- `docs/FEATURE_STATUS.md` — complete feature audit
- `docs/DEPLOYMENT.md` — Hostinger deployment guide
- `docs/TECH_STACK.md` — technology versions
- `docs/.env.production` — production environment template
- `docs/admin-panel/` — admin panel plan (15 sections)
