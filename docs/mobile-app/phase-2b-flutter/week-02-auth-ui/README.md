# Phase 2b — Week 2: Auth UI

**Goal:** Full authentication loop works on device. User can log in via all 3 methods, reset password via deep-link email, and enrol biometric.

**Design reference:** [`../../design/12-flutter-auth-onboarding.md §12.3, 12.11–12.13`](../../design/12-flutter-auth-onboarding.md)

**Screenshots needed before starting:**
- Login screen (all 3 tabs: email+password, phone+OTP, email+OTP)
- Forgot password screen
- Reset password screen
- Biometric enrol bottom sheet
- Biometric unlock screen

---

## Steps

| # | Step | Coverage |
|---|------|----------|
| 1 | step-01-onboarding-slides.md | 3-slide intro carousel (first-run only) |
| 2 | step-02-login-screen-shell.md | Login scaffold with tab bar (3 tabs) |
| 3 | step-03-login-email-password.md | Tab 1: email + password form + submit |
| 4 | step-04-login-phone-otp.md | Tab 2: phone → OTP 2-stage |
| 5 | step-05-login-email-otp.md | Tab 3: email → OTP 2-stage |
| 6 | step-06-auth-repository.md | `AuthRepository` with Dio calls |
| 7 | step-07-auth-riverpod-providers.md | `authStateProvider` — auth state machine |
| 8 | step-08-forgot-password-screen.md | Forgot password form |
| 9 | step-09-reset-password-screen.md | Reset password form (from deep link) |
| 10 | step-10-biometric-enrol.md | `local_auth` wiring + enrol bottom sheet |
| 11 | step-11-biometric-unlock.md | Launch-time biometric gate |
| 12 | step-12-device-registration.md | `POST /devices` call on every login |
| 13 | step-13-auth-integration-test.md | Widget + integration tests for auth flows |
| 14 | week-02-acceptance.md | |

---

## Acceptance

- [ ] Login via email+password → dashboard
- [ ] Login via phone+OTP → dashboard
- [ ] Login via email+OTP → dashboard (feature-flagged)
- [ ] Forgot password → email arrives → tap link → reset screen opens → new password works
- [ ] Biometric enrol → next launch prompts fingerprint → dashboard
- [ ] Logout clears token, back to login
- [ ] Wrong password shows inline error (no stack trace)
- [ ] OTP cooldown (30s) shown inline after resend
- [ ] Rate limit hit (11th login in a minute) shows friendly "try again soon"
- [ ] All API 401/403 errors gracefully land on login screen
