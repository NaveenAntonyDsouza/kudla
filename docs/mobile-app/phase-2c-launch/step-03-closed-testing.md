# Step 3 — Closed Testing (Beta) Cohort

## Goal
Invite 50 real users (engaged webview users or new prospects) to test the Flutter app as "beta". Get real-world feedback at scale before production.

## Duration
**2 weeks.**

## Procedure

### 1. Create closed testing track

Play Console → Testing → Closed testing → "Create new track"

Track name: `kudla-beta-v1`

### 2. Identify cohort

- Filter existing webview users: last_login_at < 30 days ago (engaged)
- Target 50 users, diverse by gender + age + plan tier
- Send email: "Try the new Kudla app — beta testers get 1 month free Gold"

Offer: 1-month free Gold membership (grant via Filament admin → manual subscription) as incentive.

### 3. Onboarding

Email template:
```
Subject: You're invited to try the new Kudla Matrimony app!

Hi {name},

We've built a brand new native app for Kudla Matrimony — faster, native features,
push notifications, biometric login. Free members of our beta program get 1 month
of Gold membership.

To join:
1. Reply YES to this email → we'll add you to the beta
2. Install: {play-store-beta-link}
3. Try it for 2 weeks, share feedback

Your Kudla account works seamlessly — just log in with your existing credentials.

Thanks,
Naveen
```

### 4. Feedback channels

- WhatsApp group "Kudla Beta" (50 users)
- In-app "Send feedback" shortcut (add to More menu → opens WhatsApp support)
- Weekly 1-question poll: "What's the #1 thing we should fix?"

### 5. Metrics to track

Create spreadsheet:
- Day 1 install rate (of 50 invited)
- Day 7 retention
- Day 14 retention
- Sessions per user
- Crashes per user
- Play Store reviews from beta testers
- Razorpay conversions via new app

### 6. Ship hotfixes

Every 3–4 days, release a new beta build with accumulated fixes.

### 7. Collect testimonials

Before production launch, ask top 3 beta testers for a short testimonial quote we can use on Play Store listing.

## Exit criteria

- [ ] ≥ 40 of 50 invited install the beta
- [ ] ≥ 30 complete a full session
- [ ] Day 7 retention ≥ 60% of installers
- [ ] Crash-free rate > 99%
- [ ] Average beta rating ≥ 4.2/5 (in-app prompt)
- [ ] 3 written testimonials collected

## Next step
→ [step-04-production-10-percent.md](step-04-production-10-percent.md)
