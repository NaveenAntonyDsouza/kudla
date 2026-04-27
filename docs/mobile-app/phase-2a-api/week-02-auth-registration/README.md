# Phase 2a — Week 2: Auth & Registration

**Goal:** complete authentication + 5-step registration API. By end of week, the full register → verify → login flow works via curl against a real Laravel backend.

**Design reference:** [`design/02-auth-api.md`](../../design/02-auth-api.md)

**Prerequisite:** [Week 1 acceptance](../week-01-foundations/week-01-acceptance.md) ✅ passed.

---

## Steps

| # | Step | Status |
|---|------|--------|
| # | Step | Status | Commit |
|---|------|--------|--------|
| 1 | [OTP channel migration (email support)](step-01-otp-channel-migration.md) | ✅ | `e392e83` |
| 2 | [Refactor OtpService for phone + email](step-02-otp-service-refactor.md) | ✅ | `35463bc` |
| 3 | [Extract AuthService](step-03-extract-auth-service.md) | ✅ | `eb4ef6d` |
| 4 | [Extract RegistrationService](step-04-extract-registration-service.md) | ✅ | `1460894` |
| 5 | [Api V1 FormRequest base pattern](step-05-api-form-request-pattern.md) | ✅ | `411c203` |
| 6 | [Register step 1 endpoint — public, issues token](step-06-register-step-1-endpoint.md) | ✅ | `02af5a9` |
| 7 | [Register steps 2–5 endpoints — auth required](step-07-register-steps-2-5.md) | ✅ | `4a14128` |
| 8 | [Phone OTP send + verify endpoints](step-08-phone-otp-endpoints.md) | ✅ | `288fe9e` |
| 9 | [Email OTP send + verify endpoints](step-09-email-otp-endpoints.md) | ✅ | `fd4953b` |
| 10 | [Login with password endpoint](step-10-login-password.md) | ✅ | `0322d3d` |
| 11 | [Login with phone OTP endpoint](step-11-login-phone-otp.md) | ✅ folded | *via step-8 `purpose=login`* |
| 12 | [Login with email OTP endpoint](step-12-login-email-otp.md) | ✅ folded | *via step-9 `purpose=login`* |
| 13 | [Forgot + reset password endpoints](step-13-forgot-reset-password.md) | ✅ | `40fb22a` |
| 14 | [GET /auth/me + POST /auth/logout](step-14-me-logout.md) | ✅ | `3a731a9` |
| 15 | [Device registration (FCM token store)](step-15-device-registration.md) | ✅ | `70b9457` |

**Week 2 shipped April 24, 2026.** 13 atomic commits (steps 11+12 folded — they added no new code, just documented that the purpose=login branch of the OTP verify endpoints already handles them).

## Deltas from the plan (what we learned)

- **AuthService + RegistrationService stayed API-only.** The plan said to refactor web LoginController/RegisterController to delegate. We decided the risk to live web flow (session/flash edge cases) outweighed DRY benefits. Web stays untouched; services are new code. Both paths write to identical DB rows so there's no divergence concern.
- **Steps 11+12 are not real steps.** My own plan said "no new route needed" — they're just feature flags (`mobile_otp_login_enabled`, `email_otp_login_enabled`) on the existing OTP verify endpoints. Folded into 8+9.
- **Local MySQL drift kept biting.** Every user-facing migration needed `--path=` to avoid the old pending ones. Applied 3 pending user migrations (reengagement + weekly-match + nudges) mid-step-3 because AuthService needed `reengagement_level` column. Tracking this as "do a full drift cleanup when MySQL test DB is set up" — still not blocking.
- **Device revoke couples with token revoke.** When the user taps "log out this device" from the app, revoking just the device row and leaving the Sanctum token alive would create a zombie state (no push, but API still works). Coupled the two — revoke one, revoke both.
- **Anti-enumeration discipline.** Forgot password, login with unknown email, OTP send for login/reset on unknown phone — all return envelope success. Don't leak account existence.

## End-to-week acceptance results (9/9 ✓)

Full user lifecycle flow smoke-tested end-to-end:
1. Register step-1 → token issued
2. Register steps 2–5 → all complete
3. Email OTP verify → email_verified_at set
4. `/me` → onboarding_completed=true, next_step='dashboard'
5. Register device → device_id=2
6. Logout → token revoked
7. Login via password → new token
8. Forgot + reset password → works
9. Login with new password → works

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
