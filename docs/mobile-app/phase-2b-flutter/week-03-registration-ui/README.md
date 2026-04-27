# Phase 2b — Week 3: Registration UI

**Goal:** Full 5-step registration flow on device, from fresh install to dashboard.

**Design reference:** [`../../design/12-flutter-auth-onboarding.md §12.4–12.10`](../../design/12-flutter-auth-onboarding.md)

**Screenshots needed:**
- Register step 1 (basic info form)
- Register step 2 (primary + religious — show Hindu branch, Christian branch, Muslim branch if possible)
- Register step 3 (education + professional)
- Register step 4 (location + contact)
- Register step 5 (creator info)
- Verify email screen (OTP input)
- Verify phone screen (OTP input)
- Optional: progress indicator design

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-register-shell-with-progress.md — Shell with "Step N of 5" progress |
| 2 | step-02-register-step-1-form.md — Basic fields with inline validation |
| 3 | step-03-cascade-select-widget.md — Reusable cascading dropdown |
| 4 | step-04-register-step-2-religion-branches.md — Conditional fields per religion |
| 5 | step-05-register-step-2-jathakam-upload.md — File picker + upload |
| 6 | step-06-register-step-3-form.md |
| 7 | step-07-register-step-4-form.md |
| 8 | step-08-register-step-5-form.md |
| 9 | step-09-verify-email-screen.md — OTP input + resend countdown |
| 10 | step-10-verify-phone-screen.md |
| 11 | step-11-register-repository.md — `RegistrationRepository` with 5 methods |
| 12 | step-12-register-providers.md |
| 13 | step-13-register-e2e-test.md |
| 14 | week-03-acceptance.md |

---

## Acceptance

- [ ] Fresh install → onboarding → register step 1 → 2 → 3 → 4 → 5 → verify email → dashboard
- [ ] Each step's data persists even if user backgrounds app mid-flow
- [ ] Hindu selection shows caste/gotra/nakshatra fields
- [ ] Christian selection shows denomination/diocese fields
- [ ] Muslim selection shows sect fields
- [ ] Under-18 DOB rejected inline
- [ ] Duplicate email/phone error shown with "Try logging in" CTA
- [ ] OTP resend cooldown works (30s counter)
- [ ] Jathakam upload succeeds for PDF + image
