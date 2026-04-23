# Step 2 — Internal Testing Track

## Goal
Upload AAB to Play Console's Internal Testing track. 10 internal testers install + complete full user journey. Catch bugs before real users see them.

## Duration
**1 week.**

## Procedure

### 1. Upload AAB to Internal Testing

1. Play Console → Kudla Matrimony app → Testing → Internal testing
2. "Create new release"
3. Upload `app-release.aab`
4. Release name: `1.0.0 (1)`
5. Release notes:
   ```
   First native Flutter build.
   - Full feature parity with webview
   - Push notifications
   - Biometric login
   - Razorpay native checkout
   ```
6. Review + Save → "Start rollout to Internal testing"

### 2. Add testers

**Internal testing → Testers tab:**
- Create email list "internal-testers"
- Add 10 emails — team + close family who can test

Each tester receives an opt-in link. They click → Play Store installs the app.

### 3. Test plan (circulate to testers)

Ask each tester to do:

**Core flows:**
- [ ] Register as new user (different email for each tester)
- [ ] Verify email
- [ ] Complete profile (onboarding)
- [ ] Upload a photo
- [ ] Search for matches
- [ ] Send an interest
- [ ] Accept an interest (cross-test between testers)
- [ ] Exchange a few chat messages
- [ ] Attempt a Razorpay payment (use test card `4111 1111 1111 1111` if test mode; or real ₹1 transaction if prod)

**Edge cases:**
- [ ] Login via phone OTP
- [ ] Forgot password
- [ ] Background/foreground during chat
- [ ] Lose network, regain network
- [ ] Deep link to a profile from a WhatsApp message

### 4. Feedback channel

Create a dedicated channel — WhatsApp group "Kudla App Beta Testers" or similar.

Ask testers to report:
- **Crash** — what were you doing?
- **Bug** — screenshot + steps to reproduce
- **UX issue** — confusion, wrong copy, slow screen

### 5. Monitor

Daily for 7 days:
- Firebase Crashlytics for crashes
- Play Console → Statistics for install/retention
- Feedback channel for reports

### 6. Iterate

Fix all P0 (crashes, data loss, payment) within 24h. Fix P1 within 48h. Queue P2 for later.

Each fix → new build → upload as `1.0.0 (2)`, `1.0.0 (3)`, etc.

## Exit criteria

- [ ] Zero P0 bugs open
- [ ] < 3 P1 bugs open
- [ ] Crash-free rate > 99%
- [ ] 10/10 testers complete the core flows successfully
- [ ] Payment flow tested end-to-end by ≥ 3 testers (use small real amount like ₹99 if test mode not available)

## Next step
→ [step-03-closed-testing.md](step-03-closed-testing.md)
