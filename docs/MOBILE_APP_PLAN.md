# Mobile App Plan (Flutter) — NEXT PRIORITY

> **→ For the detailed, phased implementation plan see [`docs/mobile-app/`](mobile-app/README.md)** — 17 documents covering the REST API layer (Sanctum, endpoints, response envelope) and the Flutter app (screens, state management, Firebase, Razorpay, Play Store rollout).
>
> This document remains the high-level overview; `docs/mobile-app/` is the source of truth for implementation.

**Status:** Plan finalized April 23, 2026 — see [`docs/mobile-app/16-implementation.md`](mobile-app/16-implementation.md) for kickoff steps. Admin panel + core platform + brand neutralization + CodeCanyon prep (AWS S3, demo seeder) are complete.

**Current state:**
- Existing Android webview app on Play Store: `com.books.KudlaMatrimony` (wraps kudlamatrimony.com)
- This document plans the upgrade: webview → full native Flutter app

**Sell as:** Premium tier bundle in CodeCanyon listing (Web + Flutter App) — commands higher price than web-only.

**Estimated effort:** 3-6 months end-to-end (API layer 1-2 weeks, Flutter build 2-4 months, store review + polish 2-4 weeks).

**Why Flutter over React Native / native:**
- Single codebase → Android + iOS
- Mature ecosystem (Riverpod, Dio, FCM, Razorpay SDK all first-class)
- Hot reload speeds development
- Strong performance, near-native feel

---

## Tech Stack

- **Framework:** Flutter (single codebase → Android + iOS)
- **API:** Laravel Sanctum (token-based auth) — REST API layer on existing backend
- **State Management:** Riverpod or BLoC
- **Push Notifications:** Firebase Cloud Messaging (FCM)
- **Image Handling:** cached_network_image
- **Payment:** Razorpay Flutter SDK

---

## Screens

| # | Screen | Maps to Web |
|---|--------|-------------|
| 1 | Splash + Onboarding (3 slides) | New |
| 2 | Login (phone/email + OTP) | /login |
| 3 | Registration (5 steps) | /register |
| 4 | Dashboard (matches, new profiles, stats) | /dashboard |
| 5 | My Matches (match score cards) | /matches |
| 6 | Mutual Matches | /matches/mutual |
| 7 | Search (partner prefs, keyword, ID) | /search |
| 8 | Profile View (swipeable tabs) | /profile/{id} |
| 9 | My Profile (view + edit sections) | /profile |
| 10 | Photo Management | /photos |
| 11 | Interest Inbox (tabs: received, sent, accepted, declined) | /interests |
| 12 | Chat (after interest accepted) | /interests/{id} |
| 13 | Notifications | /notifications |
| 14 | Shortlist | /shortlist |
| 15 | Who Viewed My Profile | /views |
| 16 | Membership Plans + Payment | /membership |
| 17 | Settings (visibility, alerts, hide, delete, password) | /settings |
| 18 | Profile Preview (share card) | /profile/preview |

---

## Prerequisites (build before the app)

1. **REST API layer** — Add `api.php` routes returning JSON for all controllers
2. **Laravel Sanctum** — Token auth (login returns bearer token, all API routes use `auth:sanctum`)
3. **API versioning** — `/api/v1/` prefix for future compatibility
4. **Image URLs** — Ensure all photo URLs are absolute (not relative)
5. **Push notification backend** — FCM token storage + notification dispatch

---

## Key Differences from Web

- Swipe gestures for profile browsing (Tinder-style optional)
- Pull-to-refresh on all lists
- Bottom tab navigation (Home, Search, Interests, Profile, More)
- Push notifications instead of bell icon polling
- Biometric login (fingerprint/face) for returning users
- Offline caching for viewed profiles
- Share profile card as image via WhatsApp/social

---

## Build Order (when ready)

1. API layer on existing Laravel backend (Sanctum + JSON routes)
2. Flutter project setup + auth screens
3. Dashboard + profile view + search
4. Interests + chat
5. Payments + settings
6. Push notifications
7. Polish + test + publish
