# Version Pins (April 2026)

Exact versions for every moving part. **Do not drift without updating this file + CHANGELOG.**

Last verified: **2026-04-23**

---

## Laravel (API Layer)

| Package | Version | Purpose |
|---------|---------|---------|
| `php` | 8.3.x | Laravel 13 minimum; 8.4 recommended |
| `laravel/framework` | 13.x | Framework |
| `laravel/sanctum` | 4.x | API token auth |
| `laravel/tinker` | 3.x | REPL |
| `filament/filament` | 5.4.x | Admin panel (existing) |
| `livewire/livewire` | 4.2.x | Admin UI (existing) |
| `spatie/laravel-permission` | 6.x | Roles (existing) |
| `intervention/image` | 4.x | Image processing (existing) |
| `league/flysystem-aws-s3-v3` | 3.x | S3/R2 driver (existing) |
| `endroid/qr-code` | 6.x | QR codes for share cards |
| `phpoffice/phpspreadsheet` | 5.x | Excel exports (existing) |
| `razorpay/razorpay` | 2.x | Razorpay PHP SDK |
| `kreait/laravel-firebase` | 6.x | FCM push dispatch |
| `knuckleswtf/scribe` | 5.x | API documentation generator |
| `pestphp/pest` | 4.x (via `^4.0`) | Testing |
| `pestphp/pest-plugin-laravel` | 4.x | Laravel plugin for Pest |

### Composer commands

```bash
composer require laravel/sanctum razorpay/razorpay kreait/laravel-firebase
composer require --dev knuckleswtf/scribe pestphp/pest pestphp/pest-plugin-laravel
```

---

## Flutter (Mobile App)

### Core

| Package | Version | Purpose |
|---------|---------|---------|
| Flutter SDK | 3.41.5 | Pinned via FVM |
| Dart SDK | 3.9.x | Bundled with Flutter |

### State management

| Package | Version | Purpose |
|---------|---------|---------|
| `flutter_riverpod` | 3.3.1 | State management (v3 brings offline persistence + auto-retry) |
| `riverpod_annotation` | 2.6.x | Annotation support |

### Networking

| Package | Version | Purpose |
|---------|---------|---------|
| `dio` | 5.9.2 | HTTP client |
| `dio_cache_interceptor` | 3.5.x | HTTP caching |
| `dio_cache_interceptor_hive_store` | 3.2.x | Hive backend for cache |

### Routing + storage

| Package | Version | Purpose |
|---------|---------|---------|
| `go_router` | 17.2.2 | Navigation (requires Flutter 3.32+) |
| `flutter_secure_storage` | 9.2.4 | Token + biometric flag |
| `hive_ce` | 2.19.3 | Cache DB (⚠️ use `hive_ce`, not deprecated `hive`) |
| `hive_ce_flutter` | 2.3.x | Flutter bindings |
| `shared_preferences` | 2.5.x | Simple prefs |

### Firebase

| Package | Version | Purpose |
|---------|---------|---------|
| `firebase_core` | 3.12.x | Firebase init |
| `firebase_messaging` | 16.2.0 | FCM push (April 2026 release) |
| `flutter_local_notifications` | 18.0.x | Foreground notifications |
| `flutter_app_badger` | 1.5.0 | App icon badge |

### Images

| Package | Version | Purpose |
|---------|---------|---------|
| `cached_network_image` | 3.4.x | Photo caching |
| `image_picker` | 1.1.x | Gallery / camera |
| `image_cropper` | 12.2.1 | Crop UI |
| `flutter_image_compress` | 2.4.x | Pre-upload compression |
| `photo_view` | 0.15.x | Full-screen viewer |

### Payments / auth / deep links

| Package | Version | Purpose |
|---------|---------|---------|
| `razorpay_flutter` | 1.4.4 | Razorpay native SDK (April 2026 release) |
| `local_auth` | 2.3.x | Biometric |
| `app_links` | 6.3.x | Deep links |
| `permission_handler` | 11.4.x | Runtime permissions |

### UI utilities

| Package | Version | Purpose |
|---------|---------|---------|
| `flutter_html` | 3.0.x | Render static page HTML |
| `shimmer` | 3.0.0 | Loading skeletons |
| `pull_to_refresh_flutter3` | 2.0.x | Pull-to-refresh |
| `carousel_slider` | 5.0.x | Image carousels |
| `share_plus` | 10.x | System share |
| `url_launcher` | 6.3.x | Dial, email, WhatsApp intents |
| `intl` | 0.20.x | i18n / formatting |
| `timeago` | 3.7.x | "2 hours ago" |

### Dev dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `flutter_lints` | 5.x | Baseline lints |
| `very_good_analysis` | 7.x | Stricter lints (recommended) |
| `build_runner` | 2.5.x | Codegen runner |
| `json_serializable` | 6.9.x | DTO codegen |
| `json_annotation` | 4.9.x | JSON annotations |
| `riverpod_generator` | 2.7.x | `@riverpod` codegen |
| `mocktail` | 1.1.x | Test mocks |
| `patrol` | 3.x (optional) | Integration testing |

---

## Android Build

| Setting | Value |
|---------|-------|
| `minSdkVersion` | 21 (Android 5.0) |
| `targetSdkVersion` | **36 (Android 16)** — Google Play Aug 2026 deadline |
| `compileSdkVersion` | 36 |
| Package name | `com.books.KudlaMatrimony` |
| AGP (Android Gradle Plugin) | 8.8.x |
| Gradle | 8.12 |
| Kotlin | 2.1.x |
| JDK | 17 |
| NDK (if needed) | 27.x |

---

## Firebase

| Item | Value |
|------|-------|
| Project ID | `kudla-matrimony-e3d63` |
| Project number | `772914041103` |
| Storage bucket | `kudla-matrimony-e3d63.firebasestorage.app` |
| Android app ID | `1:772914041103:android:5686af5ef78133f91cae7a` |
| `google-services.json` location | `flutter-app/android/app/google-services.json` (do not commit) |

---

## Regeneration

When bumping a version:

1. Update this file
2. Update `docs/mobile-app/CHANGELOG.md` with entry
3. Run `flutter pub upgrade` (Flutter) or `composer update` (Laravel)
4. Run full test suite
5. Commit pubspec.lock / composer.lock

**Sources:** All versions verified against pub.dev / packagist.org on 2026-04-23 via WebFetch.
