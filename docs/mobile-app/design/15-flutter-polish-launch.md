# 15. Flutter — Polish & Launch

Covers: app shell, bottom nav, pull-to-refresh, offline caching, share profile card, shimmer loaders, accessibility pass, APK signing, Play Store listing, webview → native rollout strategy.

---

## 15.1 App Shell + Bottom Navigation

**5-tab bottom nav** (StatefulShellRoute from `11-flutter-foundations.md §11.8`):

| Icon | Label | Route | Notes |
|------|-------|-------|-------|
| home | Home | `/dashboard` | default |
| search | Search | `/search` | |
| mail | Interests | `/interests` | badge with unread count |
| user | Profile | `/profile` | |
| grid | More | `/more` | opens MoreMenuScreen |

### MoreMenuScreen contents

A simple list screen for the overflow items that don't fit in 4 tabs:
- Matches → `/matches/my`
- Shortlist → `/shortlist`
- Who Viewed Me → `/views`
- Discover → `/discover`
- Notifications → `/notifications`
- Photos → `/photos`
- Membership → `/membership`
- Settings → `/settings`
- Help & Support → contact screen
- Log out

**Design placeholder:** bottom nav + more-menu.

---

## 15.2 Pull-to-Refresh

Use `pull_to_refresh_flutter3` on every list:
- Dashboard carousels container
- Search results
- Matches
- Interests inbox
- Notifications
- Shortlist / Who Viewed / Blocked / Ignored
- Discover results

**Pattern:**
```dart
SmartRefresher(
  controller: _refreshController,
  onRefresh: () async {
    await ref.refresh(someProvider.future);
    _refreshController.refreshCompleted();
  },
  child: ListView(...),
);
```

Chat thread does NOT use pull-to-refresh (it polls). Instead: pull-down to load older messages (infinite scroll up).

---

## 15.3 Shimmer Loaders

Use `shimmer` package. One `LoadingSkeleton` widget per screen shape:
- `DashboardSkeleton` — header + 2 carousel rows + stats row
- `ProfileCardListSkeleton` — 10 placeholder cards
- `ProfileViewSkeleton` — hero + 4 text blocks
- `ChatThreadSkeleton` — header + 5 message-bubble placeholders
- `SettingsListSkeleton` — rows of grey bars

**Rule:** every initial-load screen shows skeleton for max 400ms — if data arrives faster, skip skeleton (avoid flash). Use `AnimatedSwitcher` with Future.delayed guard.

---

## 15.4 Offline Caching Layer

### Cached with Hive (survives app restart)
- Site config (`/site/settings`) — always available, refreshed stale-while-revalidate
- Reference data (24h TTL per list)
- Last dashboard snapshot — shown instantly on relaunch while fresh fetch spins
- Last notifications list — shown while unread count fetches
- Last viewed profiles (max 50) — keyed by matri_id, 7-day TTL

### Cached with memory only (session)
- Current search results
- Current interests tab state

### Never cached (always fresh)
- `/profile/me` (editable — always get current server state)
- `/interests/{id}/messages` (live data)
- `/membership/me` + `/membership/history` (financial, always authoritative)
- `/auth/me` — except for the "did we have a valid session last time" check

**Photos:** `cached_network_image` handles photo caching automatically — LRU, 100 MB budget.

### Offline banner

When `connectivity_plus` reports no network:
- Show yellow banner at top: "Offline — viewing cached data"
- Disable "Send interest" / "Reply" / payment actions (they'd fail)
- Show cached data from Hive where available
- Poll reconnection every 10s; dismiss banner when back

---

## 15.5 Native Integrations

### URL launcher
- `tel:` for phone
- `mailto:` for email
- `https://wa.me/...` for WhatsApp
- `market://` for Play Store review

### Share
- `share_plus` for system share sheet
- Used in: share profile, share success story, share app install link

### External browser
- `url_launcher` with `LaunchMode.externalApplication` for admin links / help pages that aren't in-app static pages

---

## 15.6 Share Profile Card

**Purpose:** user shares their own (or someone else's visible) profile as an image to WhatsApp/social.

**Flow:**
1. User taps "Share" on profile view
2. Fetch `/api/v1/profiles/{matriId}/preview` — returns data for card
3. Render a `RepaintBoundary` with styled card layout (photo + name + age + location + matri_id + QR code + site branding)
4. `boundary.toImage(pixelRatio: 3)` → PNG bytes
5. Save to temp file
6. `Share.shareXFiles([XFile(path)], text: 'Check out this profile on Kudla Matrimony')`

**Card layout (rendered in-app):**
- Top: site logo + tagline
- Center: circular photo
- Below photo: full name + age + location
- 2-column grid: religion/caste, education/occupation, height, mother tongue
- QR code pointing to `https://kudlamatrimony.com/profile/{matriId}`
- Footer: "kudlamatrimony.com"
- Aspect ratio 9:16 for phone share, 1:1 optional for Instagram

**State:** rendered lazily — no persistent provider needed.

**Design placeholder:** the card itself needs a specific visual design.

---

## 15.7 Accessibility Pass

Before Play Store submit:
- All `TextField` has `labelText` (announced by TalkBack)
- All `IconButton` has `tooltip` / `semanticLabel`
- Images have `semanticLabel` describing content
- Color contrast: text on background ≥ 4.5:1
- Touch targets ≥ 48×48 dp
- `MediaQuery.textScaler` respected — dynamic font sizing doesn't break layouts
- Screen reader order tested on key flows (login, dashboard, send interest)

Tool: `flutter_accessibility_report` + manual TalkBack pass on physical device.

---

## 15.8 APK Signing + Release Build

### One-time — create signing key
```bash
keytool -genkey -v -keystore ~/kudla-matrimony-release.jks -keyalg RSA -keysize 2048 -validity 10000 -alias release
```

Store `kudla-matrimony-release.jks` + password in a safe place (1Password / hardware key / backed-up vault). **Losing this = can never update the app** on Play Store.

### `android/key.properties` (gitignored)
```properties
storePassword=...
keyPassword=...
keyAlias=release
storeFile=../kudla-matrimony-release.jks
```

### `android/app/build.gradle` — signing config
```gradle
def keystoreProperties = new Properties()
def keystorePropertiesFile = rootProject.file('key.properties')
if (keystorePropertiesFile.exists()) {
    keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
}

android {
    signingConfigs {
        release {
            keyAlias keystoreProperties['keyAlias']
            keyPassword keystoreProperties['keyPassword']
            storeFile file(keystoreProperties['storeFile'])
            storePassword keystoreProperties['storePassword']
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled true
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
}
```

### Build
```bash
flutter build appbundle --release --dart-define=FLAVOR=prod
# Output: build/app/outputs/bundle/release/app-release.aab
```

Upload `.aab` (not `.apk`) to Play Store — Google generates optimized APKs per device from AAB.

### Version bumping
- `pubspec.yaml` version: `1.0.0+1` → `1.0.1+2` (semver + build number)
- `versionCode` and `versionName` in `build.gradle` auto-sync from pubspec

---

## 15.9 ProGuard Rules

`android/app/proguard-rules.pro` — keep classes Razorpay + Firebase need:
```
-keep class com.razorpay.** { *; }
-keepattributes *Annotation*
-keepattributes Signature
-keep class com.google.firebase.** { *; }
-keep class com.google.gms.** { *; }
-dontwarn com.razorpay.**
-dontwarn com.google.firebase.**
```

Incomplete ProGuard = app crashes on Razorpay SDK call in release builds. Test release build on device before Play Store submit.

---

## 15.10 Play Store Listing

### Assets needed (exact specs per Play Console)
- App icon: 512×512 PNG
- Feature graphic: 1024×500 PNG
- Phone screenshots: 1080×1920 minimum, 8 max (highly recommended: 8)
- 7-inch tablet: optional (skip unless we commit to tablet)
- 10-inch tablet: optional
- Promotional video: optional, YouTube link
- Short description: 80 chars max
- Full description: 4000 chars max
- App category: Lifestyle → Matrimonial
- Content rating: PEGI 12 / ESRB Teen (dating-app-adjacent)
- Target audience: 18+

### Descriptions (draft)

**Short:** "Find your perfect match on Kudla Matrimony. Verified profiles, real conversations."

**Full:**
```
Kudla Matrimony is a trusted platform connecting singles with genuine, verified matrimonial profiles. Designed for the Mangalore community and beyond, we help you find your life partner with privacy, ease, and authenticity.

✦ Verified Profiles — Every profile is reviewed before going live
✦ Smart Matching — Our algorithm suggests profiles that align with your preferences
✦ Privacy First — You control who sees your photos and contact info
✦ Direct Communication — Chat with mutually interested matches
✦ Community Features — Browse by religion, caste, mother tongue, occupation, and more

Key Features:
• 5-step detailed profile creation
• Advanced search with 15+ filters
• Partner preference–based match suggestions
• Interest send, accept, decline workflow
• In-app chat for accepted matches
• Photo privacy controls (blur, watermark, premium-only)
• Shortlist, ignore, block, report — full moderation tools
• Multiple membership tiers with Razorpay secure payments
• Weekly match suggestions by email + push
• ID proof verification for trust
• Success stories from real matches

We're dedicated to helping you find meaningful connections — not just dates.

Download now and start your journey to marriage with Kudla Matrimony.

Contact: support@kudlamatrimony.com
Website: https://kudlamatrimony.com
```

### Keywords (for ASO)
matrimony, matrimonial, wedding, bride, groom, marriage, kannada, mangalore, shaadi, matchmaking

### Privacy Policy URL
Required — point to `https://kudlamatrimony.com/privacy-policy` (static page on web).

### Data Safety form
Declare:
- Location: NOT collected (we use user-input native/residing location only, not device GPS)
- Personal info: Name, email, phone, photos, user-provided profile fields
- Financial info: Payment processed by Razorpay (not stored by us)
- Photos: Yes, profile photos — purpose: app functionality
- Usage data: App interaction for analytics (disclose only if we actually do this)
- All marked as encrypted in transit ✓

---

## 15.11 Webview → Native Rollout

### Current state
- Old webview app: `com.books.KudlaMatrimony` on Play Store
- Wraps kudlamatrimony.com in a WebView

### Strategy: in-place replacement

Build Flutter app with **same package name** (`com.books.KudlaMatrimony`) → upload as **app update** in Play Console.

**Migration benefits:**
- Existing users get update prompt → seamless transition (no second listing to discover)
- Install count / review history preserved
- Zero marketing campaign cost to rebuild awareness

**Migration risks:**
- Signing key must match. **Check this first.** Retrieve keystore used for webview app. If lost → we need a new listing, old app users won't upgrade. Play App Signing (Google-held key) mitigates — check Play Console whether it's enabled for old app
- Any logged-in users (via cookies) lose session → first launch after update → login screen. Acceptable one-time friction

### Rollout phases in Play Console

1. **Internal testing track** (v1.0.0): 10 internal testers, 1 week. Smoke test every screen, every payment, every push.
2. **Closed testing track** (v1.0.1): 50 invited beta testers, 2 weeks. Fix 5–15 rough edges uncovered.
3. **Open testing / production 10%** (v1.0.2): opt-in, 10% rollout for 3 days. Monitor crash-free rate (target: > 99%). Address any regressions.
4. **Production 50%** (v1.0.3): 3 days. Review in-app reviews.
5. **Production 100%** (v1.0.4): full rollout. Promote in-app notification to old webview users on web side.

**If crash rate > 1% at any stage**: pause rollout, hotfix, resume from testing track.

---

## 15.12 Analytics + Crash Reporting

v1 minimal:
- **Firebase Crashlytics** — free, hooked via `firebase_crashlytics` plugin. Upload ProGuard deobfuscation files with each release for readable stack traces
- **Firebase Analytics** (optional v1) — basic event tracking for funnel analysis

Events to track (if we enable Analytics):
- `app_opened`
- `login_method_used` (password / phone_otp / email_otp)
- `registration_step_completed` (step 1..5)
- `interest_sent`
- `interest_accepted`
- `payment_attempted`
- `payment_succeeded`
- `payment_failed`
- `profile_viewed`
- `search_performed` (filter counts)

Respects user preference — don't track if `push_promotions = false` or analytics toggle exists. GDPR-friendly even though we're not in EU.

---

## 15.13 Post-launch Iteration Plan

**Week 1 post-launch:**
- Daily: check Crashlytics for top crashes → ship hotfix within 48h
- Daily: read new Play Store reviews → reply within 24h
- Monitor DAU, retention, crash-free rate

**Week 2–4:**
- Fix UX papercuts from review feedback
- Optimise slow screens (dashboard cold-start, search first-load)
- Remove placeholder screens (if any shipped as stubs)

**Month 2:**
- Start iOS build (requires Apple Developer Program $99)
- Port Flutter app to iOS — minimal changes needed (mostly Firebase APNS setup + App Store submission)

**Month 3:**
- Laravel Reverb for real-time chat (requires VPS migration off Hostinger — see `NEXT_SESSION_PLAN.md` Phase 3)
- Replace polling with WebSocket channel subscription

---

## 15.14 Support Plan

Once live, users will contact us for:
- "Can't login" — usually forgotten password or expired OTP → direct them to flow
- "Payment didn't activate membership" — check webhook logs, manually activate if Razorpay confirms success
- "Someone is harassing me" — handle via Block + Report combo; admin reviews reports in Filament
- "Can I delete my account?" — Settings → Delete; soft-delete with 30-day grace

Support channels (tier 1: self-serve):
- FAQ in-app
- Email: support@kudlamatrimony.com (Filament Contact Inbox)
- WhatsApp button (direct to support number)
- In-app Report Profile button for abuse

Tier 2 (admin): Filament admin panel shows all open reports, pending ID proofs, contact inquiries.

---

## 15.15 Build Checklist

### Shell + polish
- [ ] `AppShell` with StatefulShellRoute bottom nav + 5 tabs
- [ ] `MoreMenuScreen` for overflow items
- [ ] `LoadingSkeleton` widgets for each screen shape
- [ ] `EmptyState`, `ErrorView`, `OfflineBanner` shared widgets
- [ ] Pull-to-refresh on all list screens
- [ ] Hive cache layer for site config, reference data, dashboard, profiles
- [ ] `connectivity_plus` offline detection + banner

### Share + native
- [ ] Share Profile card builder + PNG export via RepaintBoundary
- [ ] System share integration via `share_plus`
- [ ] WhatsApp / call / email launchers on Contact screen
- [ ] Play Store "Rate the app" launcher

### Release
- [ ] Signing keystore created + `key.properties` configured
- [ ] `build.gradle` with release signing + ProGuard
- [ ] `proguard-rules.pro` with Razorpay + Firebase keep rules
- [ ] Smoke test release build on physical Android device (not emulator)
- [ ] Firebase Crashlytics initialized + test crash ships
- [ ] (Optional) Firebase Analytics + events

### Play Store
- [ ] App icon 512×512
- [ ] Feature graphic 1024×500
- [ ] 8 phone screenshots 1080×1920
- [ ] Short + full description
- [ ] Privacy Policy URL (points to live kudlamatrimony.com/privacy-policy)
- [ ] Data Safety form filled
- [ ] Content rating survey completed
- [ ] Target audience: 18+
- [ ] Internal testing track populated with team
- [ ] Staged rollout plan queued

### Rollout
- [ ] Retrieve keystore used for webview app (or confirm Play App Signing)
- [ ] Confirm same `applicationId` = `com.books.KudlaMatrimony`
- [ ] Internal test 1 week
- [ ] Closed test 2 weeks
- [ ] Production 10% 3 days
- [ ] Production 50% 3 days
- [ ] Production 100%

**Acceptance:**
- Signed AAB uploaded to Play Store
- Internal testers install + complete full user journey successfully
- Crashlytics reports < 1% crash rate on closed test
- Production rollout reaches 100% with no red flags
- Old webview users receive update and migrate cleanly
