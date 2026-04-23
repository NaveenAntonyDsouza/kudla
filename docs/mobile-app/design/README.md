# Design Reference — Mobile App

This folder holds the **architectural design** of the mobile app. These 16 documents describe WHAT we're building and WHY — they were written first, before the step-by-step execution plan.

For **how to actually build it, one step at a time**, see the three phase folders at the parent level:
- [`phase-2a-api/`](../phase-2a-api/README.md) — API layer step-by-step
- [`phase-2b-flutter/`](../phase-2b-flutter/README.md) — Flutter app step-by-step
- [`phase-2c-launch/`](../phase-2c-launch/README.md) — Play Store rollout step-by-step

## Design docs

### Part A — API Layer reference

| # | Doc | Covers |
|---|-----|--------|
| 01 | [api-foundations](01-api-foundations.md) | Sanctum, response envelope, error codes, rate limits, middleware |
| 02 | [auth-api](02-auth-api.md) | Register (5 steps), OTP (phone+email), 3 login variants, forgot password |
| 03 | [onboarding-api](03-onboarding-api.md) | 4 optional onboarding steps, reference data |
| 04 | [profile-api](04-profile-api.md) | Dashboard, own/other profile, 9 editable sections, 7-gate privacy |
| 05 | [photo-api](05-photo-api.md) | Multi-driver upload, privacy, photo requests |
| 06 | [search-discover-api](06-search-discover-api.md) | Partner search, keyword, matri-ID, saved searches, discover, matches |
| 07 | [interests-chat-api](07-interests-chat-api.md) | Interest lifecycle, chat polling |
| 08 | [membership-payment-api](08-membership-payment-api.md) | Plans, Razorpay, coupons, webhook |
| 09 | [engagement-api](09-engagement-api.md) | Notifications, shortlist, views, block, report, settings |
| 10 | [push-notifications](10-push-notifications.md) | FCM infrastructure |

### Part B — Flutter App reference

| # | Doc | Covers |
|---|-----|--------|
| 11 | [flutter-foundations](11-flutter-foundations.md) | Project scaffold, Riverpod, Dio, GoRouter, deep links, theme sync |
| 12 | [flutter-auth-onboarding](12-flutter-auth-onboarding.md) | Splash, onboarding, login, register, OTP, biometric |
| 13 | [flutter-core-screens](13-flutter-core-screens.md) | Dashboard, search, profile, photos, interests, chat, notifications |
| 14 | [flutter-membership-settings](14-flutter-membership-settings.md) | Plans, Razorpay, settings, ID proof, delete account |
| 15 | [flutter-polish-launch](15-flutter-polish-launch.md) | Bottom nav, offline, share, Play Store, rollout |

### Part C — Summary

| # | Doc | Covers |
|---|-----|--------|
| 16 | [implementation](16-implementation.md) | 20-week timeline, dependencies, go/no-go gates, manual testing, risks |

## When to use these docs vs the phase folders

| I want to… | Go to |
|-----------|-------|
| Understand what the full API looks like | Design docs (02–10) |
| Understand what each screen should do | Design docs (11–15) |
| Know what to build **right now** | Phase folder (2a or 2b) + current week subfolder |
| Look up a specific endpoint's payload | Design doc for that feature |
| See the full 20-week timeline | Design doc 16 |
| Start a new week of work | `phase-2X-*/week-NN-*/README.md` |

## Update policy

These design docs are **stable references**. We updated them on April 23, 2026 with the latest package versions + Android SDK 36 + Laravel 13 + Hive CE migration. Minor drift is allowed as implementation reveals details.

**When implementation contradicts design:** update the design doc AND add a changelog entry in [`../CHANGELOG.md`](../CHANGELOG.md). The design docs are the source of truth; the step files are execution guides.
