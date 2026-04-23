# Phase 2b — Flutter App

**Duration:** 12 weeks
**Goal:** build the Flutter Android app that consumes the Phase 2a API and delivers the full user experience.

**Prerequisite:** [Phase 2a complete](../phase-2a-api/week-04-interests-payment-push/week-04-acceptance.md) ✅

---

## Build Order

Each week has a focused scope. Demos at each checkpoint let you see incremental progress.

| Week | Folder | Deliverable |
|------|--------|-------------|
| 1 | [week-01-scaffold](week-01-scaffold/README.md) | Flutter project created, Firebase linked, Dio + Riverpod + GoRouter + secure storage wired. Splash screen loads site config from API |
| 2 | [week-02-auth-ui](week-02-auth-ui/README.md) | Login (password / phone OTP / email OTP), forgot password, reset password, biometric enrol. Full auth loop on device |
| 3 | [week-03-registration-ui](week-03-registration-ui/README.md) | Register steps 1–5 with cascading dropdowns + jathakam upload + email/phone verify |
| 4 | [week-04-dashboard-profile-view](week-04-dashboard-profile-view/README.md) | Dashboard with 7 sections + Profile View (other user) with tabs + sticky action bar |
| 5 | [week-05-search-discover](week-05-search-discover/README.md) | Partner Search with 15+ filters + keyword + matri-ID + Discover hub/category/results + Saved Searches + Matches (my + mutual) |
| 6 | [week-06-my-profile-edit](week-06-my-profile-edit/README.md) | My Profile screen + 9 section editors + preview + share card |
| 7 | [week-07-photo-manager](week-07-photo-manager/README.md) | Photo manager (3 tabs) + crop + upload + privacy + photo requests |
| 8 | [week-08-interests-inbox](week-08-interests-inbox/README.md) | Interest inbox (5 tabs) + inline accept/decline/star/trash + dashboards to interests |
| 9 | [week-09-chat-thread](week-09-chat-thread/README.md) | Chat thread + polling at 10s + accept/decline/cancel inline + reply input |
| 10 | [week-10-membership-razorpay](week-10-membership-razorpay/README.md) | Plans comparison + coupon + Razorpay SDK + payment success + receipt + history |
| 11 | [week-11-settings-notifications](week-11-settings-notifications/README.md) | Settings tree + visibility + alerts + password + hide/delete + ID proof + FCM token register + notification screen + push handlers |
| 12 | [week-12-polish](week-12-polish/README.md) | Bottom nav shell + pull-to-refresh + offline + share card + shimmer + accessibility + release build + signing |

---

## Screen Workflow

Before starting each screen, send the design screenshot. I'll read the screenshot, write the step file, and implement. You review + test on device. Iterate.

### Screens to build (40+ total)

See [`../reference/screen-catalogue.md`](../reference/screen-catalogue.md) for full flat list with statuses.

Key screens needing your screenshot before we start:

1. Splash
2. Onboarding slides (3)
3. Login (3 tabs)
4. Register step 1–5
5. Verify email / phone
6. Forgot / reset password
7. Biometric enrol + unlock
8. Dashboard
9. Profile View (with tabs)
10. My Profile (view + edit section sheet)
11. Photo manager (+ crop + privacy sheet)
12. Search (+ filter sheet + active filters bar)
13. Discover hub / category / results
14. Matches (my + mutual)
15. Profile list (shared widget for shortlist/views/blocked/ignored)
16. Interest inbox (5 tabs)
17. Chat thread (accepted + pending received)
18. Notifications
19. Membership plans
20. Checkout sheet
21. Payment success
22. Payment history
23. Settings root + sub-screens
24. ID proof
25. Delete account flow
26. Contact / FAQ / About

---

## Time budget

12 calendar weeks = ~480 hours solo full-time, or ~240 hours at half-pace. Padding for:
- Design iterations (+10%)
- Razorpay quirks (+5h)
- Play Store keystore setup (+2h)
- Device-specific bug fixing (+20h)

Realistic calendar: **12–14 weeks** for the MVP.

---

## Git workflow

- Continue on branch `phase-2-mobile`
- Commit message format: `phase-2b wk-NN: [screen]-[action] [brief]`
  - Example: `phase-2b wk-04: dashboard - shimmer loader + empty state`
- Merge to `main` at end of week 12 (Phase 2b → Phase 2c transition)
- Tag: `mobile-v1.0.0-rc1`

---

## Weekly acceptance pattern

Each week has a `week-NN-acceptance.md` at the end with:
- Screens done vs pending
- Device test on physical Android
- Screenshot comparison (design vs built)
- Performance check (smooth scroll, no jank)
- Error handling (offline, bad network, invalid input)

---

**Start:** [Week 1 — Scaffold →](week-01-scaffold/README.md)
