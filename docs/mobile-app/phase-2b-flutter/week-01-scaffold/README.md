# Phase 2b — Week 1: Flutter Scaffold

**Goal:** Flutter project initialized, runs on device, connects to API, loads theme from `/site/settings`, stores auth token securely. Enough foundation for Week 2's auth work.

**Design reference:** [`../../design/11-flutter-foundations.md`](../../design/11-flutter-foundations.md)

---

## Steps (to be detailed as we execute)

### Mon — Project creation
- [ ] step-01-project-creation.md — `flutter create` with org com.books, package `com.books.KudlaMatrimony`
- [ ] step-02-fvm-pin-flutter-version.md — Pin Flutter 3.41.5 via FVM
- [ ] step-03-folder-structure.md — Create `lib/` folder tree from design doc §11.3

### Tue — Dependencies
- [ ] step-04-pubspec-dependencies.md — pubspec.yaml with all pinned versions from reference/version-pins.md
- [ ] step-05-android-manifest-config.md — Package name, minSdk 21, targetSdk 36, permissions
- [ ] step-06-env-flavors.md — `AppConfig` with dev/staging/prod via `--dart-define=FLAVOR=...`

### Wed — Firebase
- [ ] step-07-firebase-setup.md — flutterfire configure, drop `google-services.json`
- [ ] step-08-gradle-google-services.md — Android gradle config for Firebase

### Thu — Core infrastructure
- [ ] step-09-secure-storage.md — `SecureStorage` wrapper around flutter_secure_storage
- [ ] step-10-hive-ce-init.md — Hive CE cache setup
- [ ] step-11-dio-client.md — Dio + interceptors (Auth, ErrorHandler, Cache, Logger)
- [ ] step-12-riverpod-providers.md — Base providers (apiClient, secureStorage, siteConfig)
- [ ] step-13-error-exception-mapping.md — `ApiException` class + error handler

### Fri — Navigation + theme
- [ ] step-14-go-router.md — GoRouter with route constants + auth guard
- [ ] step-15-theme-from-api.md — Fetch /site/settings, build ThemeData from response
- [ ] step-16-splash-screen.md — Splash with 3s cap + cached site-config fallback
- [ ] step-17-deep-links.md — App Links + assetlinks.json route
- [ ] step-18-error-maintenance-screens.md — Shared widgets: ErrorView, MaintenanceScreen, UpdateRequiredScreen

### Week-end
- [ ] week-01-acceptance.md

---

## Expected outcome

At end of Week 1:
- `flutter run` boots the app on Android emulator
- Splash screen shows theme colors loaded from production API
- Login screen visible (placeholder — real auth comes Week 2)
- Deep link `https://kudlamatrimony.com/profile/AM100042` opens the app

---

## Acceptance

- [ ] Project runs on physical Android device (min SDK 21, tested on Android 10+)
- [ ] Theme colors match web (fetched from production API via `/site/settings`)
- [ ] Token storage works: write, read, delete via tinker-equivalent in dev tools
- [ ] Deep link opens app to correct screen
- [ ] App doesn't crash on network offline — shows error screen

**Start by asking me for the splash + login screenshot designs.**

**Screenshots I need before writing step files for Week 2:**
1. Splash design
2. Onboarding slides (3)
3. Login screen with 3 tabs
