# 2. Auth API

Covers: register (5 steps), OTP (phone + email), login (3 variants), forgot password, logout, `GET /me`, token lifecycle.

**Source material:** `App\Http\Controllers\Auth\LoginController`, `App\Http\Controllers\Auth\RegisterController`, `App\Http\Controllers\Auth\ForgotPasswordController`, `App\Services\OtpService`, `App\Models\OtpVerification`, `App\Models\LoginHistory`.

**Principle:** reuse the existing FormRequest classes (`RegisterStep1Request`, …) where the validation rules are identical. If the API needs different rules (e.g. no CSRF dependency), subclass them.

---

## 2.1 Session → Token Conversion

### What changes

| Web flow | API flow |
|----------|---------|
| Session cookie tracks logged-in user | Sanctum personal access token (Bearer) |
| CSRF tokens on POST | No CSRF (token auth) |
| `Auth::login($user)` | Token returned in JSON: `{"token": "5|abc...xyz"}` |
| `redirect()->route(...)` | Response includes `next_step` hint for client-side nav |
| Email-OTP lives in session | Email-OTP moves to `otp_verifications` table (same table as phone, distinguished by `channel` column) |

### Migration: extend `otp_verifications` to support email

```php
Schema::table('otp_verifications', function (Blueprint $table) {
    $table->string('channel', 10)->default('phone')->after('otp_code');  // 'phone' | 'email'
    $table->string('destination')->after('channel');                      // phone number OR email
    $table->index(['channel', 'destination']);
});
```

`OtpService::sendOtp($phone)` becomes `OtpService::send(string $destination, string $channel)`. Web's existing phone flow stays identical (`channel='phone'`).

This also fixes the web-side email-OTP session bug noted in research — shared hosting sessions can be flaky.

---

## 2.2 Registration — 5 Steps

### Step 1: `POST /api/v1/auth/register/step-1`

**No auth required.** Creates `User` + `Profile` rows, returns a Sanctum token so steps 2–5 are authenticated.

**Request:**
```json
{
  "full_name": "Naveen D'Souza",
  "email": "naveen@example.com",
  "phone": "9876543210",
  "password": "secret123",
  "password_confirmation": "secret123",
  "gender": "Male",
  "date_of_birth": "1995-04-12",
  "ref": "MNG"                    // optional, affiliate code
}
```

**Validation:** same rules as web `RegisterStep1Request`. Age ≥ `config('matrimony.registration_min_age')` = 18. Password 6–14 chars. Phone digits:10. Email unique. Phone unique.

**Response 201:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 42,
      "name": "Naveen D'Souza",
      "email": "naveen@example.com",
      "phone": "9876543210",
      "branch_id": 3,
      "email_verified_at": null,
      "phone_verified_at": null
    },
    "profile": {
      "matri_id": "AM100042",
      "onboarding_step_completed": 1,
      "onboarding_completed": false,
      "is_approved": true
    },
    "token": "5|WGNdkrOzGwpNR...",
    "next_step": "register.step-2"
  }
}
```

**Controller behaviour:**
- Calls `RegistrationService::createFreeAccount($validated)` (extract the existing logic from `RegisterController::storeStep1` into a Service method — web controller becomes a thin wrapper too)
- `AffiliateTracker::attributeRegistration()` — accepts `ref` as either a cookie or request param (need a small patch here so API can pass `?ref=` in body)
- Creates token: `$user->createToken('flutter', ['*'])->plainTextToken`
- Returns token + user + profile

### Step 2: `POST /api/v1/auth/register/step-2`

**Auth required (Bearer token from step 1).**

**Request:**
```json
{
  "height": 170,
  "complexion": "Wheatish",
  "body_type": "Average",
  "physical_status": "Normal",
  "marital_status": "Never Married",
  "children_with_me": 0,
  "children_not_with_me": 0,
  "family_status": "Middle Class",
  "religion": "Hindu",
  "caste": "Brahmin",
  "sub_caste": "Saraswat",
  "gotra": "Bharadwaj",
  "nakshatra": "Rohini",
  "rashi": "Taurus",
  "manglik": "No",
  "denomination": null,                // Christian only
  "diocese": null,
  "diocese_name": null,
  "parish_name_place": null,
  "time_of_birth": "06:30",
  "place_of_birth": "Mangalore",
  "muslim_sect": null,                 // Muslim only
  "muslim_community": null,
  "religious_observance": null,
  "jain_sect": null,                   // Jain only
  "other_religion_name": null,
  "da_category": null,                 // only if physical_status = 'Differently Abled'
  "da_category_other": null,
  "da_description": null
}
```

**Jathakam upload:** moved to a separate endpoint — `POST /api/v1/profile/me/jathakam` (multipart, PDF only). Don't multiplex multipart into step-2 JSON.

**Response 200:**
```json
{
  "success": true,
  "data": {
    "profile": { "onboarding_step_completed": 2, ... },
    "next_step": "register.step-3"
  }
}
```

### Steps 3, 4, 5 — same pattern

- **Step 3:** `education_level`, `educational_qualification`, `occupation`, `employer_name`, `working_country`, `company_description` → upserts `education_details`
- **Step 4:** `native_country`, `native_state`, `native_district`, `pin_zip_code`, `whatsapp_number`, `mobile_number`, `custodian_name`, `custodian_relation`, `communication_address` → upserts `location_info` + `contact_info`
- **Step 5:** `created_by`, `creator_name`, `creator_contact_number`, `how_did_you_hear_about_us` → finalizes Profile, sets `onboarding_step_completed=5`, triggers verification flow

**Step 5 response includes next_step logic:**
```json
{
  "success": true,
  "data": {
    "next_step": "verify.email",        // or "verify.phone" or "complete"
    "email_verification_enabled": true,
    "phone_verification_enabled": false,
    "user": { "email_verified_at": null, "phone_verified_at": null }
  }
}
```

Flutter routes based on `next_step`.

---

## 2.3 OTP — Phone

### Send: `POST /api/v1/auth/otp/phone/send`

**No auth required** (registration flow) OR **auth required** (login flow — caller passes token, server reads `user.phone`).

**Request:**
```json
{ "phone": "9876543210", "purpose": "register" }   // purpose: "register" | "login" | "reset"
```

**Validation:** phone exists for `purpose=login` or `purpose=reset`. For `register`, the user calling must be authenticated and we use their registered phone — ignore the body if set.

**Response 200:**
```json
{ "success": true, "data": { "sent": true, "expires_in_seconds": 600, "cooldown_seconds": 30 } }
```

**Response 429 on cooldown:**
```json
{ "success": false, "error": { "code": "OTP_COOLDOWN", "message": "Please wait 28 seconds before requesting another code." } }
```

**Controller calls:** `OtpService::send($phone, 'phone', $purpose)`. Existing `OtpService::sendOtp` is renamed — web controllers updated to call new signature (backwards-compatible wrapper can stay for 1 release).

### Verify: `POST /api/v1/auth/otp/phone/verify`

**Request:**
```json
{ "phone": "9876543210", "otp": "123456", "purpose": "register" }
```

**Response 200 (register purpose):**
```json
{
  "success": true,
  "data": {
    "verified": true,
    "user": { "phone_verified_at": "2026-04-23T14:32:11Z" },
    "onboarding_completed": true,    // might flip to true if this was the last gate
    "next_step": "dashboard"         // or "verify.email" if email OTP still pending
  }
}
```

**Response 200 (login purpose):**
```json
{
  "success": true,
  "data": {
    "token": "8|AbcXyz...",
    "user": { ... },
    "profile": { "onboarding_step_completed": 3, "onboarding_completed": false },
    "next_step": "register.step-4"    // if onboarding incomplete
  }
}
```

**Dev mode short-circuit:** in `local` env, accept OTP `123456` without DB lookup (matches existing dev convenience in `LoginController::sendEmailLoginOtp` line 133).

---

## 2.4 OTP — Email

Mirror of phone OTP. Differences:

- `POST /api/v1/auth/otp/email/send` — body: `{"email": "...", "purpose": "register|login|reset"}`
- `POST /api/v1/auth/otp/email/verify` — body: `{"email": "...", "otp": "123456", "purpose": "..."}`
- Server calls `OtpService::send($email, 'email', $purpose)`

**Transport:** raw `Mail::raw(...)` for now (matches existing web behaviour). Upgrade path: `OtpEmail` mailable with branded template when time allows (tracked in Phase 3 polish).

---

## 2.5 Login — 3 variants

### `POST /api/v1/auth/login/password`

**Request:**
```json
{ "email": "naveen@example.com", "password": "secret123", "device_name": "Pixel 8 Pro" }
```

**Validation:** email + password required. `device_name` optional (labels the token in `personal_access_tokens.name` — shows in "active sessions" list).

**Response 200:**
```json
{
  "success": true,
  "data": {
    "token": "12|NewXyz...",
    "user": { "id": 42, "name": "...", "email": "...", "phone": "...", "email_verified_at": "...", "phone_verified_at": null, "role": "user", "is_active": true },
    "profile": { "matri_id": "AM100042", "onboarding_completed": true, "onboarding_step_completed": 5, "profile_completion_pct": 78 },
    "membership": { "plan": "Free", "ends_at": null, "is_premium": false },
    "next_step": "dashboard"        // or "register.step-N" if onboarding incomplete
  }
}
```

**Response 401:**
```json
{ "success": false, "error": { "code": "UNAUTHENTICATED", "message": "Invalid email or password." } }
```

**Side effects:** `last_login_at = now()`, `reengagement_level = 0`, `LoginHistory::record($user, 'password')` — same as web.

### `POST /api/v1/auth/login/phone-otp`

Two-step. Client calls `/otp/phone/send` with `purpose=login`, then `/otp/phone/verify` with `purpose=login`. The verify endpoint returns a token directly (see 2.3).

**Gating:** respects `SiteSetting mobile_otp_login_enabled` (same as web).

### `POST /api/v1/auth/login/email-otp`

Same pattern as phone-OTP login. Respects `SiteSetting email_otp_login_enabled`.

---

## 2.6 Forgot Password

### `POST /api/v1/auth/password/forgot`

**Request:**
```json
{ "email": "naveen@example.com" }
```

**Response 200** (always, don't leak whether email exists):
```json
{ "success": true, "data": { "sent": true, "message": "If that email is registered, a reset link has been sent." } }
```

**Implementation:** calls Laravel's `Password::sendResetLink(['email' => $email])` — existing password broker, existing `.env` MAIL config. The reset email contains a link: `https://app.kudlamatrimony.com/reset-password/{token}`.

**Deep link:** that link must open the Flutter app on Android (via App Links — see `15-flutter-polish-launch`). Inside the app it navigates to the reset screen with the token.

### `POST /api/v1/auth/password/reset`

**Request:**
```json
{ "token": "abc...", "email": "naveen@example.com", "password": "new-secret", "password_confirmation": "new-secret" }
```

**Response 200:** `{"success": true, "data": {"reset": true}}` — Flutter shows success screen, prompts re-login.

---

## 2.7 `GET /me` and token lifecycle

### `GET /api/v1/auth/me`

**Response:** same shape as login success minus `token`. Flutter calls this on app launch to validate the stored token (if 401, drop token and show login).

### `POST /api/v1/auth/logout`

Revokes **current** token only (not all devices). `$request->user()->currentAccessToken()->delete()`.

**Response 200:** `{"success": true, "data": {"logged_out": true}}`.

### Token TTL

- Sanctum default: no expiry (token valid until revoked)
- Set `'expiration' => 60 * 24 * 90` in `config/sanctum.php` → 90-day tokens
- Flutter calls `GET /me` on each app launch. If 401, goes through login flow. If 200, proceeds to dashboard.
- **No refresh token flow.** Mobile login is cheap (biometric unlock); no need for refresh tokens.

### Multi-device

- User can log in from N devices — each gets its own token
- `GET /api/v1/auth/devices` (future — not v1) lists active tokens with `name`, `last_used_at`
- **v1:** device registration piggybacks on `POST /devices` (FCM token store) which also stores `personal_access_token_id` FK. Logout via `/devices/{id}` revokes that token.

---

## 2.8 Session edge cases

| Web behaviour | API handling |
|---------------|--------------|
| "Remember me" checkbox extends session | Tokens are long-lived by default, no "remember me" field |
| Redirect to `register.step-N` if onboarding incomplete | Response `next_step` field + Flutter router reads it |
| Redirect to `verify.email` after step 5 | Response `next_step: "verify.email"` |
| `profile.complete` middleware blocks dashboard | API dashboard endpoint returns `PROFILE_INCOMPLETE` error with `next_step` field |
| CSRF token needed on POST | Not needed for Sanctum token auth |
| `last_login_at` + `reengagement_level` reset | Same behaviour on login endpoints |
| `LoginHistory::record($user, $type)` | Called on every successful login (`password`, `phone_otp`, `email_otp`) |

---

## 2.9 Device Registration (FCM token)

See `10-push-notifications.md` for full detail. Summary here:

### `POST /api/v1/devices`

**Request:**
```json
{
  "fcm_token": "dpU-k3z...",
  "platform": "android",
  "device_model": "Pixel 8 Pro",
  "app_version": "1.0.0",
  "os_version": "14"
}
```

**Response 201:**
```json
{ "success": true, "data": { "device_id": 17 } }
```

Called after every login + whenever FCM token refreshes. Dedupes on `fcm_token + user_id` — updates in place if same token re-registers.

---

## 2.10 Controllers & Service Split

**Create `App\Services\RegistrationService`** with these methods (extracts from current `RegisterController`):
- `createFreeAccount(array $data, ?string $affiliateRef = null): array` → returns `['user' => ..., 'profile' => ...]`
- `updatePrimaryReligious(Profile $profile, array $data): void`
- `updateEducation(Profile $profile, array $data): void`
- `updateLocationContact(Profile $profile, array $data): void`
- `finalizeRegistration(Profile $profile, array $data): string` → returns next_step

**Create `App\Services\AuthService`** with:
- `authenticatePassword(string $email, string $password): ?User`
- `authenticatePhoneOtp(string $phone, string $otp): ?User`
- `authenticateEmailOtp(string $email, string $otp): ?User`
- `issueToken(User $user, string $deviceName): string`
- `revokeCurrentToken(User $user): void`

Refactor existing web `LoginController`/`RegisterController` to call these too — single source of truth.

---

## 2.11 Build Checklist

- [ ] Migration: `otp_verifications.channel` column + `destination` column
- [ ] `OtpService::send(string $destination, string $channel, string $purpose)` new signature; old `sendOtp` is a wrapper
- [ ] `App\Services\RegistrationService` — extract all Register step logic
- [ ] `App\Services\AuthService` — extract login + token issuance
- [ ] `App\Http\Controllers\Api\V1\AuthController` — all OTP + login + logout + me + password reset
- [ ] `App\Http\Controllers\Api\V1\RegistrationController` — steps 1–5
- [ ] `App\Http\Controllers\Api\V1\DeviceController` — register/revoke FCM
- [ ] FormRequests in `App\Http\Requests\Api\V1\*` (subclass existing ones with JSON-friendly error shape)
- [ ] Pest tests — one per endpoint, covering happy path + 1 error path each

**Acceptance:** full register→verify→dashboard loop works via curl from a clean DB, returning tokens at every step and ending with `next_step: "dashboard"` on the verify response.
