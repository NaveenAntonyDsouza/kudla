# Phase 2a — Week 2: Auth & Registration

**Goal:** complete authentication + 5-step registration API. By end of week, the full register → verify → login flow works via curl against a real Laravel backend.

**Design reference:** [`design/02-auth-api.md`](../../design/02-auth-api.md)

**Prerequisite:** [Week 1 acceptance](../week-01-foundations/week-01-acceptance.md) ✅ passed.

---

## Steps

| # | Step | Status |
|---|------|--------|
| 1 | [OTP channel migration (email support)](step-01-otp-channel-migration.md) | ☐ |
| 2 | [Refactor OtpService for phone + email](step-02-otp-service-refactor.md) | ☐ |
| 3 | [Extract AuthService](step-03-extract-auth-service.md) | ☐ |
| 4 | [Extract RegistrationService](step-04-extract-registration-service.md) | ☐ |
| 5 | [Api V1 FormRequest base pattern](step-05-api-form-request-pattern.md) | ☐ |
| 6 | [Register step 1 endpoint — public, issues token](step-06-register-step-1-endpoint.md) | ☐ |
| 7 | [Register steps 2–5 endpoints — auth required](step-07-register-steps-2-5.md) | ☐ |
| 8 | [Phone OTP send + verify endpoints](step-08-phone-otp-endpoints.md) | ☐ |
| 9 | [Email OTP send + verify endpoints](step-09-email-otp-endpoints.md) | ☐ |
| 10 | [Login with password endpoint](step-10-login-password.md) | ☐ |
| 11 | [Login with phone OTP endpoint](step-11-login-phone-otp.md) | ☐ |
| 12 | [Login with email OTP endpoint](step-12-login-email-otp.md) | ☐ |
| 13 | [Forgot + reset password endpoints](step-13-forgot-reset-password.md) | ☐ |
| 14 | [GET /auth/me + POST /auth/logout](step-14-me-logout.md) | ☐ |
| 15 | [Device registration (FCM token store)](step-15-device-registration.md) | ☐ |

**End-of-week acceptance:** [week-02-acceptance.md](week-02-acceptance.md)

---

## End-of-week state

Working curl flows:

1. **Fresh register + verify:**
   ```
   POST /api/v1/auth/register/step-1    → token + user
   POST /api/v1/auth/register/step-2    → authed, adds religion+personal
   POST /api/v1/auth/register/step-3    → adds education
   POST /api/v1/auth/register/step-4    → adds location
   POST /api/v1/auth/register/step-5    → sets creator info, returns next_step
   POST /api/v1/auth/otp/email/send     → email sent
   POST /api/v1/auth/otp/email/verify   → email_verified_at set
   GET  /api/v1/auth/me                 → full user + profile + membership
   ```

2. **Login variants:**
   ```
   POST /api/v1/auth/login/password
   POST /api/v1/auth/login/phone-otp (2-step)
   POST /api/v1/auth/login/email-otp (2-step)
   ```

3. **Forgot password:**
   ```
   POST /api/v1/auth/password/forgot → email sent
   POST /api/v1/auth/password/reset  → password changed
   ```

4. **Logout + device registration:**
   ```
   POST /api/v1/devices               → FCM token stored
   POST /api/v1/auth/logout           → current token revoked
   ```

---

## Time budget

~24 hours of focused work → **1 working week** (5 working days).

| Day | Steps | Coverage |
|-----|-------|----------|
| Mon | 1, 2, 3 | Migrations + service layer |
| Tue | 4, 5, 6 | Registration service + form requests + step 1 |
| Wed | 7, 8, 9 | Register steps 2-5 + OTP |
| Thu | 10, 11, 12 | Login variants |
| Fri | 13, 14, 15, acceptance | Forgot password, me/logout, devices, final acceptance |

---

## Git workflow

One commit per step. Commit message format: `phase-2a wk-02: step-NN [title]`

At end of week:
```bash
git log --oneline phase-2-mobile
# Should show 15 new commits for week 2
```

---

**Start:** [Step 1 — OTP channel migration →](step-01-otp-channel-migration.md)
