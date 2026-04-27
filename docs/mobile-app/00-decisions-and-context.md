# Decisions & Context

Everything we've locked in, with the reasoning. Read this once; don't re-litigate.

Last updated: **April 23, 2026**

---

## Part 1 — Business Context

### The company we're operating
- **Kudla Matrimony** — matrimonial platform for the Mangalore community, live at [kudlamatrimony.com](https://kudlamatrimony.com)
- Built on MatrimonyTheme (Laravel 13, PHP 8.3, Filament 5.4 admin, Livewire, Tailwind v4)
- Deployed April 23, 2026 with all Phase 1 features (admin panel, staff module, franchise, theme/branding)

### The CodeCanyon angle
- We're preparing MatrimonyTheme for CodeCanyon distribution — web-only script ~$89, Web + Flutter app ~$199–299
- Kudla is our own operation; we build the mobile app for Kudla first, then bundle for CodeCanyon buyers
- Every decision considers: "does this work for a CodeCanyon buyer configuring their own Kudla-like site?"

### Existing webview app
- Package name: `com.books.KudlaMatrimony`
- Live on Google Play Store
- Wraps kudlamatrimony.com in a WebView — limited UX, no push, no biometric
- We **replace** this app via Play Store update (same package name, same store listing)

### Why native over webview
- Push notifications (engagement driver #1 in matrimony category)
- Biometric quick-login
- Native UX (tabs, gestures, animations)
- Offline cache for last-viewed profiles
- Share profile card as image

---

## Part 2 — Technology Decisions (locked)

### Backend — API Layer

| Decision | Choice | Why |
|----------|--------|-----|
| **Framework** | Laravel 13 (released March 17, 2026) | Already using 13 on web; no split |
| **PHP** | 8.3 minimum | Laravel 13 requires it |
| **Auth package** | Laravel Sanctum | Official, token-based, multi-device, no refresh tokens needed |
| **Token type** | Personal Access Token | Long-lived, hashed at rest, rotated on login |
| **Token transport** | `Authorization: Bearer <token>` header | Mobile-standard |
| **Controller pattern** | Thin `App\Http\Controllers\Api\V1\*` → existing `App\Services\*` | Services are already factored; API controllers become ~20-line JSON adapters |
| **Response shape** | `{success, data}` or `{success:false, error:{code, message, fields}}` | Predictable, typable in Flutter |
| **Error codes** | Stable string enums (`VALIDATION_FAILED`, `DAILY_LIMIT_REACHED`, …) | Flutter switches on codes, not HTTP status |
| **API URL** | `kudlamatrimony.com/api/v1` (same domain) | Simpler than subdomain; no new SSL/DNS config |
| **Versioning** | `/api/v1/` prefix from day 1 | Mobile apps on Play Store can't be rolled back — API contract must be stable |
| **Rate limits** | 60/min/user default; tighter on OTP/login | Anti-abuse |
| **API docs** | Scribe (`knuckleswtf/scribe`) | Auto-generates OpenAPI 3.1 + Postman + HTML from Laravel code |
| **Testing** | Pest v4 | Cleaner than PHPUnit, parallel by default |
| **Linting** | Laravel Pint (already installed) | Matches existing web codebase |

### Frontend — Flutter

| Decision | Choice | Why |
|----------|--------|-----|
| **Framework** | Flutter 3.41.5 | Current stable, matches package ecosystem |
| **Dart SDK** | 3.9.x (bundled with Flutter) | |
| **Version pinning** | FVM (Flutter Version Manager) | Eliminates "works on my machine" drift |
| **State management** | Riverpod 3.3.1 (`flutter_riverpod`) | Offline persistence + auto-retry in v3, simpler than BLoC |
| **HTTP client** | Dio 5.9.2 | Interceptors, caching, progress callbacks |
| **Routing** | go_router 17.2.2 | Deep-link native, typed routes, shell routes for bottom nav |
| **Secure storage** | `flutter_secure_storage` 9.2.4 | Bearer token, biometric flag |
| **Cache/local DB** | `hive_ce` 2.19.3 | ⚠️ original `hive` is unmaintained — use Community Edition |
| **Images** | `cached_network_image` 3.4.x, `image_picker` 1.1.x, `image_cropper` 12.2.1, `flutter_image_compress` 2.4.x | Standard stack |
| **Push** | `firebase_messaging` 16.2.0 + `flutter_local_notifications` 18.0.x | FCM direct, not OneSignal (free, full control) |
| **Payments** | `razorpay_flutter` 1.4.4 | Official Razorpay SDK, native checkout UI |
| **Biometric** | `local_auth` 2.3.x | Fingerprint/face unlock |
| **Deep links** | `app_links` 6.3.x + Android App Links (manifest intent filter) | Native, no Firebase Dynamic Links (deprecated) |
| **Linting** | `very_good_analysis` 7.x | Stricter than flutter_lints |
| **Testing** | `flutter_test` + `integration_test` + `mocktail` | Standard; `patrol` if needed later |

### Infrastructure

| Decision | Choice | Why |
|----------|--------|-----|
| **API hosting** | Hostinger shared (same as web) | Already working; $0 extra |
| **Queue worker** | Cron `queue:work --stop-when-empty --max-time=55` every minute | Hostinger doesn't support long-running daemons; this pattern works |
| **Real-time chat** | **Polling every 10s** (v1) | Hostinger can't run WebSocket daemons. Laravel Reverb deferred to Phase 3 after VPS migration |
| **Photo storage** | Multi-driver (Local/Cloudinary/R2/S3) — already configured | No change |
| **Firebase** | Project `kudla-matrimony-e3d63`, service-account JSON at `storage/app/firebase-credentials.json` | One-time setup, free tier adequate |

### Android Build

| Decision | Choice | Why |
|----------|--------|-----|
| **Package name** | `com.books.KudlaMatrimony` | Matches existing webview app — enables update-in-place on Play Store |
| **Target SDK** | 36 (Android 16) | Google Play's August 2026 deadline requires API 36; we target from day 1 |
| **Compile SDK** | 36 | Matches target |
| **Min SDK** | 21 (Android 5.0) | 99%+ device coverage |
| **Signing** | Play App Signing (Google holds app signing key) + our own upload key | Already enrolled — confirmed from Play Console screenshot |
| **ProGuard** | Enabled for release | Razorpay + Firebase keep rules added |
| **Flavors** | dev / staging / prod | via `--dart-define=FLAVOR=...` |

### Launch Strategy

| Decision | Choice | Why |
|----------|--------|-----|
| **First platform** | Android only (v1) | Matches existing webview. iOS deferred to v2 after native Android is stable |
| **iOS timing** | Phase 3 (post-v1 stability) | Needs Apple Developer Program $99/year + Mac access |
| **Rollout** | Internal (1w) → Closed (2w) → Prod 10% (3d) → 50% (3d) → 100% | Catches regressions before mass users |
| **Store listing** | Preserve existing `com.books.KudlaMatrimony` listing | Keeps install count, reviews, ASO ranking |

---

## Part 3 — Process Decisions

### Timeline
- **Phase 2a** (API): 4 weeks
- **Buffer + Bruno smoke test**: 1 week
- **Phase 2b** (Flutter): 12 weeks
- **Phase 2c** (Launch): 4 weeks
- **Total: ~21 weeks** → ship to Play Store around **mid-September 2026**

### Who builds what
- **User (Naveen) + me (Claude) — solo pair**
- User handles:
  - Design screenshots per screen (before we build it)
  - Real-world testing on devices
  - Razorpay account + Firebase account + Play Store submission
  - Business decisions (pricing, content, support policies)
- Claude handles:
  - All code (Laravel API + Flutter)
  - Architecture decisions within locked stack
  - Step-by-step guides before each work session
  - Tests

### Screens workflow
1. Before starting each screen, Claude asks for the design screenshot
2. User shares screenshot → Claude reads it, writes implementation step file, builds it
3. User tests the screen on device, reports issues
4. Iterate until ✓

### Git workflow
- Feature branch: `phase-2-mobile` for all API + Flutter work
- Commit in logical chunks (one feature at a time: auth, photos, interests, …)
- Merge to `main` at end of each phase (2a, 2b, 2c)
- Tag each released version: `mobile-v1.0.0`, `mobile-v1.0.1`, …

### Documentation workflow
- Design docs in `design/` are reference — updated only when we learn something
- Step files in `phase-2X-*/` are execution — updated as we complete them
- Reference docs (version-pins, error-codes) updated when reality drifts

---

## Part 4 — Pre-Kickoff Status

| Item | Status | Notes |
|------|--------|-------|
| Firebase project `kudla-matrimony-e3d63` | ✅ Created | `google-services.json` received, will drop into `flutter-app/android/app/` |
| Razorpay test keys | ✅ Generated by user | User holds them, will share when Week 4 hits |
| Play Store signing | ✅ Signing by Google Play | Update-in-place safe; existing install base + reviews preserved |
| Logo (flat PNG) | ✅ Received | |
| Logo (hearts-only for icon) | ⏳ TODO — not urgent | |
| Logo source file (Figma/AI/PSD) | ⏳ TODO — not urgent | |
| Hostinger SSH + cron | ✅ Confirmed | Queue worker via cron pattern works |
| Design screenshots | ⏳ Per-screen, just-in-time | User will share as we approach each screen |

---

## Part 5 — Things NOT In Scope (tracked elsewhere)

| Item | Where it lives |
|------|---------------|
| iOS build | Phase 3 — after v1 stable |
| Laravel Reverb (real-time chat) | Phase 3 — after VPS migration |
| Apple Sign-in / Google Sign-in | Phase 3+ |
| Play Billing (in-app purchases) | Not needed; matrimony category allows direct payments |
| Widgets / rich push / lock screen | Phase 3+ polish |
| Offline-first write queue | Phase 3+ |
| Accessibility audit (full TalkBack pass) | Part of Phase 2c polish, not Phase 2b |
| Localisation (Hindi, Kannada, Tulu, Malayalam) | Phase 4 — see `NEXT_SESSION_PLAN.md` |
| Bulk CSV import | Phase 3 CodeCanyon prep |
| Installation wizard + Envato licensing | Phase 3 CodeCanyon prep |
| Wedding Directory module | Phase 3 |
| Meilisearch | When >50K profiles |

---

## Part 6 — Ask-Me-Before-Changing Rules

These aren't decisions yet; they're guardrails. **Claude should confirm before breaking any of these:**

1. **Don't rename `com.books.KudlaMatrimony`** — changes the package identity, breaks update-in-place
2. **Don't change the API response envelope shape** — breaks Flutter clients in flight
3. **Don't change `/api/v1/` prefix** — would require Flutter update to match
4. **Don't remove Laravel Pint config** — matches existing web codebase style
5. **Don't bump major Flutter version mid-phase** — can cause package cascade breakage
6. **Don't change Firebase project ID** — re-tying all FCM tokens
7. **Don't switch away from Hostinger** unless pushed by user — keeps infra familiar
8. **Don't enable web push** — Phase 4, not Phase 2
9. **Don't commit `firebase-credentials.json` or `key.properties` or `.jks` files** — secrets
10. **Don't push to Play Store from Claude** — user has sole access; user decides when to promote a build

When in doubt, stop and ask.
