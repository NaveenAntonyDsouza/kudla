# Glossary

Terms, acronyms, and package names used throughout the plan.

---

## Project-specific

| Term | Meaning |
|------|---------|
| **Kudla Matrimony** | Our flagship matrimony site for Mangalore community |
| **MatrimonyTheme** | The codebase itself — sold on CodeCanyon as a white-label script |
| **matri_id** | Public profile identifier like `AM100042` (stable, shareable) |
| **Onboarding** | Optional 4 post-registration steps that boost profile completion |
| **Registration** | Required 5-step signup flow |
| **Section** | One of 9 editable profile areas (primary, religious, education, etc.) |
| **Interest** | A match-request one user sends to another |
| **Photo Request** | A request to see another user's gated photos |
| **Shortlist** | User's "favorites" list of profiles |
| **Ignore** | "Don't show me this profile" (soft-filter in search) |
| **Block** | Hard block — both parties invisible to each other |
| **Premium** | Active `UserMembership` with `is_active=true` |
| **VIP / Featured** | Admin-promoted badges for boosted visibility |
| **Webview app** | The existing Android app wrapping kudlamatrimony.com — being replaced |

## Technical — API

| Term | Meaning |
|------|---------|
| **Sanctum** | Laravel's token-auth package |
| **Personal Access Token** | Sanctum's long-lived bearer token type (what we use) |
| **Bearer** | The `Authorization: Bearer <token>` header format |
| **Envelope** | Our response shape: `{success, data}` or `{success:false, error:{code, message}}` |
| **Scribe** | Tool that generates API docs from Laravel code |
| **Pest** | Modern PHPUnit alternative for testing |
| **FormRequest** | Laravel class that wraps validation + authorization |
| **Resource** | Laravel class that transforms Model → JSON |
| **Service** | `App\Services\*` — business logic layer (Thin controller + service pattern) |

## Technical — Flutter

| Term | Meaning |
|------|---------|
| **Flutter** | Google's cross-platform UI framework (Dart-based) |
| **Dart** | The language Flutter uses |
| **FVM** | Flutter Version Manager — pins Flutter SDK per project |
| **Riverpod** | Our state management package (v3) |
| **Provider** | Riverpod unit of shared state (reactive) |
| **Dio** | HTTP client for Dart |
| **GoRouter** | Declarative routing for Flutter |
| **Hive CE** | Community Edition of Hive (NoSQL local DB) — original `hive` is unmaintained |
| **Secure Storage** | `flutter_secure_storage` — encrypted key-value (tokens, biometric flag) |
| **Interceptor** | Dio plugin that runs on every request/response (auth, errors, cache) |
| **Shell Route** | GoRouter pattern for bottom-nav layouts |

## Technical — Android / Play Store

| Term | Meaning |
|------|---------|
| **AAB** | Android App Bundle — uploaded to Play Store (Google generates per-device APKs) |
| **APK** | Android Package — an installable (used for side-loading tests) |
| **minSdkVersion** | Lowest Android version the app runs on (we use 21 = Android 5.0) |
| **targetSdkVersion** | Android API level the app is built against (we use 36 = Android 16) |
| **compileSdkVersion** | Android SDK version at compile time (we use 36) |
| **Package name** | Unique ID (`com.books.KudlaMatrimony`) |
| **Keystore** | File holding signing key (the `.jks` file) |
| **Play App Signing** | Google-managed app-signing key (we have this enrolled) |
| **Upload key** | Our signing key; Google strips + re-signs with app-signing key |
| **ProGuard** | Code obfuscation + shrinking (Android release builds) |
| **App Links** | Verified deep links (HTTPS URLs open in our app) |
| **Adaptive icon** | Android launcher icon with separate foreground + background layers |

## Technical — Firebase

| Term | Meaning |
|------|---------|
| **FCM** | Firebase Cloud Messaging (push notifications) |
| **APNS** | Apple Push Notification Service (iOS — Phase 3) |
| **google-services.json** | FCM config downloaded for Android app |
| **Crashlytics** | Firebase crash reporting |
| **Analytics** | Firebase event tracking (optional) |

## Technical — Payments

| Term | Meaning |
|------|---------|
| **Razorpay** | Indian payment gateway (our provider) |
| **Order** | Razorpay's transaction intent — created server-side before opening SDK |
| **Payment ID** | Razorpay's confirmation after user pays |
| **Signature** | HMAC-SHA256 of `order_id + payment_id`, verified server-side |
| **Webhook** | Razorpay-to-our-server callback for async payment events |
| **Paise** | 1/100 of 1 INR — Razorpay amounts are in paise |

## Build / Deploy

| Term | Meaning |
|------|---------|
| **Phase 2a** | API layer build (4 weeks) |
| **Phase 2b** | Flutter app build (12 weeks) |
| **Phase 2c** | Play Store launch (4 weeks) |
| **Phase 3** | CodeCanyon packaging + iOS + Reverb + Directory (post-mobile launch) |
| **Staged rollout** | Google Play's phased release to X% of users |
| **Hotfix** | Emergency patch released between normal cycles |
