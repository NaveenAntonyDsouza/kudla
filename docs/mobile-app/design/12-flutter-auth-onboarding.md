# 12. Flutter — Auth & Onboarding Screens

Covers: splash, first-run onboarding carousel, login (3 tabs), registration (5 steps), OTP screens, forgot password, biometric enrolment.

**Screens in this doc:** 1, 2, 3, + registration sub-screens.

**Pattern for each screen below:**
- **Purpose** — one line
- **Entry points** — where user arrives from
- **API calls** — from the plan in Part A
- **State** — Riverpod providers read/written
- **Navigation out** — taps lead to
- **Empty / error / loading** states
- **Design placeholder** — you provide screenshot before we build

---

## 12.1 Splash Screen

**Purpose:** entry point; decide where to route based on auth + site config.

**Entry points:** cold app launch. Deep links arrive mid-load — held until splash logic finishes.

**API calls:**
- `GET /api/v1/site/settings` (cached; stale-while-revalidate)
- `GET /api/v1/auth/me` (only if token present)

**State:**
- Reads: `siteConfigProvider`, `authStateProvider`
- Writes: none

**Navigation out:**
- First-time user (no token, no preferences flag) → `/onboarding-slides`
- Returning but logged out → `/login`
- Logged in, `onboarding_completed=false` → `/register/step-{next}`
- Logged in, needs email/phone verification → `/verify/email` or `/verify/phone`
- Logged in, fully onboarded → `/dashboard`
- Version below minimum_supported_version → `/update-required`
- Deep link queued → handled after navigation decision

**States:**
- Loading (max 3s shown): logo + shimmer
- Error: full-screen "Can't reach server, tap to retry"

**Design placeholder:** logo centered, branded background color, optional loading indicator.

---

## 12.2 Onboarding Slides (first-run only)

**Purpose:** 3-slide carousel intro before first-time users see login/register.

**Entry points:** Splash → when no `has_seen_onboarding` flag in SharedPreferences.

**API calls:** none.

**State:**
- Writes: `has_seen_onboarding = true` in SharedPreferences when "Get Started" tapped.

**Slides (default copy; admin-editable in future phase):**
1. "Find Your Match" — illustrated couple + tagline
2. "Verified Profiles Only" — trust signals (ID verified badges)
3. "Your Privacy, Your Rules" — photo privacy controls

**Navigation out:**
- "Get Started" → `/login`
- "Skip" on any slide → `/login`

**States:** no async.

**Design placeholder:** 3 screenshots — illustration, headline, body text, page indicator dots.

---

## 12.3 Login Screen

**Purpose:** allow existing user to sign in via password, phone OTP, or email OTP.

**Entry points:** splash (logged out), onboarding "Get Started", deep-link "Already have account? Log in" from register.

**Tabs (visible conditionally based on site config):**
1. **Email + Password** (always shown)
2. **Phone + OTP** (shown if `features.mobile_otp_login_enabled`)
3. **Email + OTP** (shown if `features.email_otp_login_enabled`)

**Common elements:**
- Header: logo + "Welcome back"
- Tab switcher
- "Forgot password?" link (tab 1 only)
- "Don't have an account? Register" link at bottom
- Social login placeholders (Google, Apple) — **disabled in v1**, visible as "Coming soon"

### Tab 1: Email + Password

**Fields:** email, password.

**Submit behaviour:**
- Validate inline (email format, password non-empty)
- Call `POST /auth/login/password` with `{email, password, device_name: <phone model>}`
- Success → store token, register FCM device, navigate per `next_step`
- Error `UNAUTHENTICATED` → inline error "Invalid email or password"
- Error `PROFILE_SUSPENDED` → navigate to blocked screen with support contact
- Loading: button shows spinner, form disabled

### Tab 2: Phone + OTP

Two stages in the same screen:

**Stage A (enter phone):**
- Field: phone (10 digits, prefix `+91` fixed for India — adjust if international)
- Submit → `POST /auth/otp/phone/send` with `{phone, purpose: "login"}`
- On success → switch to Stage B (same screen, OTP input slides up)

**Stage B (enter OTP):**
- 6-digit code input with auto-focus + auto-submit on completion
- "Didn't receive? Resend in 30s" countdown (cooldown from config)
- Submit → `POST /auth/otp/phone/verify` with `{phone, otp, purpose: "login"}`
- Success → token returned; register device; navigate per `next_step`

### Tab 3: Email + OTP

Same shape as Tab 2 but with `email` field and `/otp/email/*` endpoints.

**API calls summary:**
- `POST /auth/login/password`
- `POST /auth/otp/phone/send` + `/verify` (purpose=login)
- `POST /auth/otp/email/send` + `/verify` (purpose=login)
- `POST /devices` (after login, FCM registration)

**State:**
- Local form state in StatefulWidget
- Writes token via `authStateProvider.login()`
- Writes device via `deviceRegistrarProvider.registerCurrentDevice()`

**Navigation out:**
- Success → `/dashboard` or `/register/step-{next}` or verify screens
- "Register" link → `/register/step-1`
- "Forgot password" → `/forgot-password`

**States:**
- Loading: disabled form, spinner
- Error: inline banner or field error based on code
- Validation: inline field errors

**Design placeholder:** header illustration, tab switcher, 2 inputs, primary button, footer links. Needs screenshot.

---

## 12.4 Register — Step 1: Basic Account

**Purpose:** collect essential account fields; create User + Profile; issue token; start multi-step flow.

**Entry:** `/login` → "Register" link, or `/onboarding-slides` "Get Started" (if we choose to route there first).

**Fields:**
- Full name
- Gender (Male/Female radio)
- Date of birth (date picker, age ≥ 18)
- Email
- Phone (+91 prefix)
- Password (6–14 chars, show/hide toggle)
- Confirm password
- "I agree to Terms & Privacy Policy" checkbox (required, links open in-app via `flutter_html`)
- Hidden: `ref` affiliate code if deep-link set one

**Submit:**
- Client validation (all fields, password match, age ≥ 18)
- `POST /auth/register/step-1`
- On success → store token → navigate to `/register/step-2`
- On error `VALIDATION_FAILED` with `fields.email` or `fields.phone` → show "Email already registered, log in?" with CTA

**Progress indicator:** "Step 1 of 5" at top.

**Design placeholder:** form with 7 inputs + checkbox + submit button.

---

## 12.5 Register — Step 2: Primary + Religious

**Purpose:** collect height, body type, marital status, religion + religion-specific fields.

**Fields (dynamic based on religion):**
- Height (slider, cm ↔ inches toggle)
- Complexion (dropdown)
- Body type (dropdown)
- Physical status (dropdown; if "Differently Abled", show 3 more fields)
- Marital status (dropdown; if not "Never Married", show children-with-me + children-not-with-me number pickers)
- Family status (dropdown)
- Religion (dropdown from `/reference/religions`)
- **Hindu-only:** caste, sub_caste, gotra, nakshatra, rashi, manglik
- **Christian-only:** denomination, diocese, diocese_name, parish_name_place
- **Muslim-only:** muslim_sect, muslim_community, religious_observance
- **Jain-only:** jain_sect
- **Other religion:** other_religion_name (free text)
- Always: time_of_birth (time picker, optional), place_of_birth (text, optional)
- Jathakam upload (separate button — opens file picker, PDF only, deferred upload)

**Cascading selects:**
- Religion selected → fetch `/reference/castes?religion=Hindu`
- Caste selected → fetch `/reference/sub-castes?caste=Brahmin`
- Religion=Christian → fetch `/reference/denominations?religion=Christian`
- Denomination selected → fetch `/reference/dioceses?denomination=Catholic`

**Submit:**
- `POST /auth/register/step-2` with all fields
- Jathakam: separate call `POST /profile/me/jathakam` after step-2 succeeds (multipart)
- Navigate to `/register/step-3`

**Design placeholder:** multi-section form. Screenshot will drive exact grouping.

---

## 12.6 Register — Step 3: Education & Professional

**Fields:**
- Education level (dropdown)
- Educational qualification (dropdown; cascades from education level)
- Occupation (dropdown)
- Employer name (text)
- Working country (dropdown)
- Company description (text, optional)

**Submit:** `POST /auth/register/step-3` → `/register/step-4`.

---

## 12.7 Register — Step 4: Location & Contact

**Fields:**
- Native country (dropdown from `/reference/countries`)
- Native state (cascades from country)
- Native district (cascades from state)
- Pin/zip code
- WhatsApp number (default: same as registered phone, editable)
- Mobile number (same default)
- Custodian name + relation (optional — for family-created profiles)
- Communication address (multi-line text)

**Submit:** `POST /auth/register/step-4` → `/register/step-5`.

---

## 12.8 Register — Step 5: Profile Ownership

**Fields:**
- "Who created this profile?" radio: Self / Parent / Sibling / Relative / Friend
- If not Self: creator name + creator contact number (both required)
- "How did you hear about us?" dropdown (optional — metric tracking)

**Submit:** `POST /auth/register/step-5` → response includes `next_step`:
- `verify.email` → `/verify/email`
- `verify.phone` → `/verify/phone`
- `complete` → `/dashboard`

---

## 12.9 Verify Email

**Purpose:** email OTP verification gate after registration.

**Entry:** step-5 response `next_step: "verify.email"`.

**Flow:**
1. Screen loads → automatically call `POST /auth/otp/email/send` with `{email: <registered>, purpose: "register"}`
2. Show 6-digit OTP input + "Resent to naveen@example.com" banner
3. Resend button: 30s cooldown, then active
4. Submit → `POST /auth/otp/email/verify` with `{email, otp, purpose: "register"}`
5. On success → response includes new `next_step`:
   - `verify.phone` (phone verification also enabled) → navigate there
   - `dashboard` → navigate there

**Error handling:**
- `OTP_INVALID` → shake OTP input + inline message
- `OTP_EXPIRED` → auto-trigger new send
- `OTP_COOLDOWN` → show remaining time inline

---

## 12.10 Verify Phone

Same structure as Verify Email, with phone OTP endpoints.

---

## 12.11 Forgot Password

**Purpose:** send password reset email.

**Entry:** Login screen → "Forgot password?" link.

**Fields:** email.

**Submit:**
- `POST /auth/password/forgot`
- Always succeed (never leak whether email exists)
- Navigate to confirmation screen: "If that email is registered, a link is on its way. Check your inbox."
- "Try a different email" link returns here

---

## 12.12 Reset Password (deep link arrival)

**Purpose:** handle the password reset link emailed to user.

**Entry:** deep link `https://kudlamatrimony.com/reset-password/{token}` → in-app navigation to this screen.

**Fields:** email (pre-filled from token query if possible — otherwise user types), new password, confirm.

**Submit:**
- `POST /auth/password/reset` with `{token, email, password, password_confirmation}`
- Success → "Password updated. Log in with your new password." → auto-route to `/login`
- Error → inline

---

## 12.13 Biometric Enrolment Prompt

**Purpose:** offer fingerprint/face quick-login on first successful login.

**Entry:** shown **once** after first successful login on a device, via bottom sheet modal on dashboard first render.

**Prompt copy:**
- "Sign in faster with fingerprint"
- Buttons: "Enable" / "Not now"

**Enable flow:**
1. Call `LocalAuth.canCheckBiometrics` — abort if device doesn't support
2. `LocalAuth.authenticate(localizedReason: 'Confirm fingerprint')` — user authenticates
3. On success → `secureStorage.setBiometricEnabled(true)` → next app launch, show "Unlock with fingerprint" option on login screen

**"Not now":** dismisses; shown again only if user opts in via Settings.

**Biometric unlock flow (subsequent launches):**
- Splash sees `biometric=true` + valid token → shows fingerprint prompt before routing to dashboard
- User authenticates → go to dashboard
- User cancels → go to login screen (token discarded? — no, keep it; user just has to pass biometric again later)

---

## 12.14 Data Layer (feature-level)

Each screen calls a method on `AuthRepository` or `RegistrationRepository`:

```dart
class AuthRepository {
  Future<AuthResult> loginPassword(String email, String password, String deviceName);
  Future<void> sendPhoneOtp(String phone, {required String purpose});
  Future<AuthResult> verifyPhoneOtp(String phone, String otp, {required String purpose});
  // ... mirror for email OTP
  Future<void> forgotPassword(String email);
  Future<void> resetPassword(String token, String email, String password);
  Future<void> logout();
  Future<UserProfile> fetchMe();
}

class RegistrationRepository {
  Future<RegistrationResult> step1(RegisterStep1Dto dto);
  Future<RegistrationResult> step2(RegisterStep2Dto dto);
  Future<RegistrationResult> step3(RegisterStep3Dto dto);
  Future<RegistrationResult> step4(RegisterStep4Dto dto);
  Future<RegistrationResult> step5(RegisterStep5Dto dto);
  Future<void> uploadJathakam(File file);
}
```

Providers wrap these with loading/error state.

---

## 12.15 Validation Utilities

Share across all forms:

```dart
class Validators {
  static String? email(String? v) { ... }
  static String? phoneIN(String? v) { ... }           // 10 digits, no leading 0
  static String? password(String? v) { ... }          // 6-14 chars
  static String? age18Plus(DateTime? dob) { ... }
  static String? nonEmpty(String? v, String field) { ... }
  static String? pincodeIN(String? v) { ... }         // 6 digits
}
```

Consistent error copy across all forms.

---

## 12.16 Build Checklist

- [ ] `SplashScreen` with routing logic in §12.1
- [ ] `OnboardingSlidesScreen` with 3 slides + page indicator
- [ ] `LoginScreen` with 3 tabs (Email+Password, Phone+OTP, Email+OTP)
- [ ] `RegisterStep1Screen` through `RegisterStep5Screen` with progress indicator
- [ ] `VerifyEmailScreen`, `VerifyPhoneScreen` with auto-send on mount + resend cooldown
- [ ] `ForgotPasswordScreen`, `ResetPasswordScreen`
- [ ] `BiometricEnrolmentBottomSheet` triggered on first dashboard load
- [ ] `BiometricUnlockScreen` shown on launch if enrolled
- [ ] `AuthRepository` + `RegistrationRepository` + typed DTOs
- [ ] Riverpod providers for state (loading, error, result)
- [ ] Cascade select widget with loading/error/empty states
- [ ] Reference data caching layer (24h Hive)

**Acceptance:**
- From `flutter run` fresh install → onboarding → register 5 steps → verify email → dashboard
- Login via each of 3 tabs works when feature toggle on
- Forgot password email triggers reset link flow
- Biometric enabled on device → next launch prompts fingerprint before dashboard

**Screens needing design screenshots (you'll provide):**
1. Splash
2. Onboarding slides (3)
3. Login (3 tabs — show the active state for each)
4. Register step 1
5. Register step 2 (show all religion branches if possible)
6. Register step 3
7. Register step 4
8. Register step 5
9. Verify email (OTP input)
10. Verify phone (OTP input)
11. Forgot password
12. Reset password
13. Biometric enrolment bottom sheet
14. Biometric unlock screen

**Ask:** for each screen above, ping me with the screenshot before I implement it.
