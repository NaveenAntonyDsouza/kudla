# Phase 2b — Week 1 Acceptance

**Date closed:** 2026-04-30
**Mobile repo:** [NaveenAntonyDsouza/kudla-mobile](https://github.com/NaveenAntonyDsouza/kudla-mobile)
**Tag:** `mobile-v0.1.0-week-01-scaffold`

---

## Acceptance criteria (from `README.md`)

| | Criterion | Result |
|---|---|---|
| ✅ | Project runs on physical Android device (min SDK 21, tested on Android 10+) | Xiaomi 220333QBI, Android 13 (API 33). `fvm flutter run -d 116b1ae7` boots cleanly. |
| ✅ | Theme colors match web (fetched from production API via `/site/settings`) | `siteConfigProvider` fetches the JSON, `AppTheme.fromSiteConfig` builds Material 3 ThemeData. Splash header renders in `#dc2626` (Tailwind red-600) — matching the web. |
| ✅ | Token storage works: write, read, delete via tinker-equivalent in dev tools | `SecureStorage` (`lib/core/storage/secure_storage.dart`) with EncryptedSharedPreferences (Android Keystore). API surface: `writeToken`, `readToken`, `clearToken`, `setBiometricEnabled`, `clearAll`. Wired into `AuthInterceptor` for automatic Bearer attachment. |
| ⏸ | Deep link opens app to correct screen | **Deferred to Week 2.** AndroidManifest has the intent filter scaffold reserved; `assetlinks.json` route on backend pending. App Links land alongside the auth flow that interprets the linked-to screens. |
| ✅ | App doesn't crash on network offline — shows error screen | `siteConfigProvider` falls back to cached value via Hive CE (`AppCache`); on cold start with no cache, `AppTheme.fallbackLight()` keeps the UI rendering with sensible defaults. |

**Net result:** 4 of 5 met; deep link explicitly deferred to Week 2 per documented scope. Phase 2b Week 1 closed.

---

## Toolchain delivered

| Tool | Version | Notes |
|---|---|---|
| Flutter | **3.41.8** (FVM-pinned) | Latest stable as of 2026-04-27. Doc had 3.41.5; we tracked latest patch. |
| Dart | 3.11.5 | Bundled. |
| FVM | 4.0.5 | Cache moved to `D:\dev\fvm\` post-disk-cleanup. |
| Android target SDK | 36 | Google Play Aug 2026 deadline. |
| Min SDK | 21 | 99%+ device coverage. |
| AGP | 8.x via Flutter Gradle Plugin | Java 17 required + enabled. |
| NDK | 28.2.13676358 | Auto-downloaded by AGP (NDK 27 deleted as malformed during scaffolding). |

---

## Deps locked (drift from `version-pins.md` documented)

- `flutter_riverpod` `3.3.1` (kept)
- `firebase_core` `^4.7.0` (bumped from doc's `^3.12.0` because `firebase_messaging 16.2.0` requires core 4.x)
- `firebase_messaging` `16.2.0` (kept)
- `riverpod_annotation` `^4.0.0` + `riverpod_generator` `^4.0.0` (bumped from doc's 2.x — annotations/generator versioning diverged from `flutter_riverpod` post-v3)
- `mocktail` `^1.0.0` (corrected from doc's `^1.1.0` which doesn't exist on pub.dev)
- `flutter_app_badger` REMOVED (1.5.0 unmaintained, missing AGP 8 `namespace`); will swap in `app_badge_plus` Week 6+
- All other Week-1 deps per doc

---

## FCM round-trip proof

The headline acceptance gate. Confirms the entire chain works end-to-end on a real Android device:

```
Flutter App  →  POST /api/v1/devices  →  Laravel + Sanctum  →  devices table
                                                                      ↓
Xiaomi push tray  ←  Google FCM  ←  Firebase Admin SDK  ←  NotificationService::send
```

### Test artifacts

- **Test user:** `id=83`, email `fcm-test@example.com`, matri_id `KM100081`
- **Sanctum token:** id=3, name=`fcm-debug-mobile` (deleted post-acceptance)
- **Device row:** id=2, real Xiaomi FCM token (fcm_token starting with `…Y48QGMvaERgHsCfEugpE`)
- **Push payload:** title `"Phase 2b Week 1 proof"`, body `"Flutter -> FCM -> Android round-trip works."`, data `{source: 'week-01-flutter-proof'}`
- **Dispatched:** 2026-04-30 02:21:48 UTC
- **Received:** within ~2 seconds (system notification tray on Xiaomi 220333QBI)

### Reproducibility (for Week 2+ regression checks)

```bash
# 1. Mint a fresh test user + Sanctum token
curl -s -X POST https://kudlamatrimony.com/api/v1/auth/register/step-1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "FCM Tester",
    "gender": "male",
    "date_of_birth": "1995-04-12",
    "phone": "9999988887",
    "email": "fcm-test@example.com",
    "password": "fcmpwd2026"
  }'
# Capture data.token from the response.

# 2. Get the token id (Sanctum needs <id>|<hash> format)
ssh kudla-prod 'cd domains/kudlamatrimony.com/public_html && \
  php artisan tinker --execute="\
    \$u = \App\Models\User::where(\"email\", \"fcm-test@example.com\")->first(); \
    \$t = \$u->createToken(\"fcm-debug-mobile\")->plainTextToken; \
    echo \$t;"'
# Save as e.g. "5|FreshHashHere..."  ID and HASH go to two env vars below.

# 3. Build + run Flutter with the token split into two env vars
#    (avoids the pipe-eating issue of Windows shell -> cmd.exe -> .bat handoff)
fvm flutter run -d 116b1ae7 \
  --dart-define=DEBUG_BEARER_ID=5 \
  --dart-define=DEBUG_BEARER_HASH=FreshHashHere

# 4. Tap "Register FCM" on the splash — wait for "✓ Registered with API. device_id=N"

# 5. Dispatch from prod
ssh kudla-prod << 'SSHEOF'
cd domains/kudlamatrimony.com/public_html
php artisan tinker --execute='
  $u = \App\Models\User::where("email", "fcm-test@example.com")->first();
  app(\App\Services\NotificationService::class)->send($u, "system", "FCM regression check",
    "Round-trip from Mobile to Android.", null, ["source" => "fcm-regression"]);
  echo "DISPATCHED";
'
SSHEOF

# 6. Confirm push lands within ~2 seconds on the Xiaomi.
```

(Note: the debug FCM register button on splash was stripped in commit `4fdcc4d` after acceptance. To re-test, restore it from `dc3e2aa..4fdcc4d` revert range OR re-add via `app_badge_plus`-pattern dev-only build. The button code is design-doc §11.5+§11.10 territory; not hard to reproduce.)

---

## Commits delivered (in order)

| Commit | Date | Subject |
|---|---|---|
| `96c6c64` | 2026-04-28 | add(scaffold): flutter create --platforms=android --org com.books |
| `b19256b` | 2026-04-28 | add(scaffold): lib/ folder structure per design doc 11.3 |
| `991c5d3` | 2026-04-29 | add(fvm): pin Flutter 3.41.8 via .fvmrc; gitignore .fvm/ + secret files |
| `64c370a` | 2026-04-29 | add(deps,android): pubspec deps + rename package to com.books.KudlaMatrimony |
| `0be77fe` | 2026-04-29 | add(config): env.dart with AppConfig dev/staging/prod flavors |
| `436055f` | 2026-04-29 | add(config): actually add env.dart (fixup for 0be77fe) |
| `4761dec` | 2026-04-29 | fix(android): enable core library desugaring; drop unmaintained flutter_app_badger |
| `0b73530` | 2026-04-29 | fix(gitignore): also ignore android/build/ |
| `5dde8a8` | 2026-04-29 | add(firebase): wire Firebase.initializeApp + ProviderScope; flutterfire configure for Android |
| `751d75e` | 2026-04-29 | add(firebase): commit firebase.json (flutterfire metadata) |
| `d151776` | 2026-04-29 | add(core): SecureStorage, Hive CE cache, Dio client + interceptors + ApiException |
| `1981324` | 2026-04-30 | add(router): GoRouter scaffold with route constants and 4 placeholder screens |
| `c384a3a` | 2026-04-30 | add(config,theme): SiteConfigData model + Hive-cached provider; AppTheme builder |
| `a726dc7` | 2026-04-30 | add(splash): real splash screen with /site/settings fetch + 3s timeout fallback |
| `dc3e2aa` | 2026-04-30 | add(splash): debug FCM register button gated by --dart-define=DEBUG_BEARER |
| `99295f8` | 2026-04-30 | add(main): rewire to MaterialApp.router + SiteConfig-driven theme; drop counter UI |
| `4d94405` | 2026-04-30 | fix(splash): split DEBUG_BEARER into two env vars to dodge shell pipe-eating |
| `ae07e6e` | 2026-04-30 | fix(splash): truncate device_model + os_version to DeviceController validator caps |
| `4fdcc4d` | 2026-04-30 | add(splash): strip debug FCM register button — Week 1 acceptance |

Plus `mobile-v0.1.0-week-01-scaffold` tag at `4fdcc4d`.

---

## Backend changes (Laravel repo, `kudla.git`)

- `config/firebase.php` — wrapped `FIREBASE_CREDENTIALS` resolution in `\App\Support\FirebaseCredentialsResolver` so relative paths (the form `.env.example` documents) resolve via `base_path()` instead of CWD. Without this, web-context FCM dispatches would silently fail because PHP-FPM's CWD is `public/`. Added Pest unit test in `tests/Unit/Support/FirebaseCredentialsResolverTest.php`. Discovered + fixed during Thursday's prod creds setup.
- Production `.env` got `FIREBASE_CREDENTIALS` populated (absolute path; will switch to relative after the `base_path()` patch is deployed).
- Production `storage/app/firebase-credentials.json` uploaded (chmod 600).

---

## Open follow-ups

1. **`MEMORY.md` task: "Deploy `FirebaseCredentialsResolver` patch + .env update"** — patch is committed locally to `kudla.git`; needs `git pull` on prod + `php artisan config:clear` + revert `.env` to relative path. Naveen's call when to schedule.
2. **App Links / deep links** — deferred to Week 2 acceptance.
3. **Real fonts** — `theme.heading_font: "Playfair Display"` and `body_font: "Inter"` are received from the API but not yet rendered (no `google_fonts` package). Material default (Roboto) is used. Plumbing is in place — flip a switch in `AppTheme` once the package is added.
4. **Real logo** — placeholder (heart-on-rounded-square) used. Naveen has the flat PNG; bundle as `assets/logo/kudla.png` in Week 2.
5. **`app_badge_plus`** (or maintained fork) for icon badge counts — Week 6+ when notifications screen lands.
6. **Test user `fcm-test@example.com` deleted post-acceptance.** Recreate via the curl in §"Reproducibility" above when needed.
7. **Placebo device row (id=1, fcm_token=`shell-test-token-2026-04-30`) deleted post-acceptance.**

---

## Week 2 starts Monday 2026-05-04

Per the original 12-week plan: **auth flows** (login, registration, OTP verification, password reset) — design screenshots from Naveen are the long-pole input.
