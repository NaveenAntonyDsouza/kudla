# Step 4 — Production 10% Rollout

## Goal
Release to 10% of existing webview users via Play Store staged rollout. Catches regressions before they hit 100% of users.

## Duration
**3 days.**

## Procedure

### 1. Promote beta build to production

Play Console → Production → Create release
- Reuse AAB from Closed Testing (or upload latest with accumulated fixes)
- Release notes (public — shown on Play Store):
  ```
  Welcome to the new native Kudla Matrimony app!

  ✨ NEW:
  • Push notifications for matches, interests, messages
  • Biometric login (fingerprint/face)
  • Faster, native user experience
  • Smarter match suggestions
  • Improved photo management

  Your existing account works right away — just log in.

  Questions? Contact support@kudlamatrimony.com
  ```

### 2. Configure staged rollout

- Rollout percentage: **10%**
- Google selects users randomly
- Review + "Start rollout to Production"

### 3. Monitor (daily checks)

- Firebase Crashlytics:
  - Crash-free users % (target: ≥ 99%)
  - Top 3 crashes → fix or note for next release
- Play Console → Statistics:
  - Installs / uninstalls ratio (< 20% uninstall is healthy)
  - ANR (App Not Responding) rate < 0.5%
- Play Store reviews (new in last 72h)
- Support emails / WhatsApp volume

### 4. Halting triggers

Pause rollout immediately if:
- Crash-free rate < 98%
- ANR rate > 1%
- Payment flow reported broken
- Login flow reported broken
- > 5 one-star reviews in 24h

To pause: Play Console → Production → pause active rollout.

### 5. Communication

If all green at Day 3:
- Internal Slack/WhatsApp: "✅ 10% rollout stable. Proceeding to 50%."

If red:
- Diagnose root cause
- Ship fix as `1.0.1`
- Re-upload as new release; rollout restarts at 10%

## Exit criteria

- [ ] 3 days with crash-free rate ≥ 99%
- [ ] ANR rate < 0.5%
- [ ] No P0 bugs surfaced
- [ ] Play Store reviews majority ≥ 4 stars
- [ ] Razorpay payment success rate > 95% (match or beat webview baseline)

## Next step
→ [step-05-production-50-percent.md](step-05-production-50-percent.md)
