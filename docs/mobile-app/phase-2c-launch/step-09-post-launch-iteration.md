# Step 9 — Post-Launch Iteration Plan

## Goal
Define a 3-month iteration plan after 100% rollout.

## Month 1 — Stabilise

- Fix all crashes > 0.1% user impact
- Address top 10 Play Store review complaints
- Hit 99.5% crash-free rate
- Bring DAU to 80%+ of webview peak

## Month 2 — Polish

- Loading perf: target < 1.5s for dashboard cold start
- Bundle size: target < 25 MB
- Accessibility pass: TalkBack full journey
- Share card improvements
- Search performance (consider Meilisearch if > 20K profiles)

## Month 3 — iOS Prep

- Apple Developer Program enrolment ($99/year)
- Mac access set up
- iOS Firebase config
- APNS certificate upload
- iOS build + smoke test
- TestFlight → App Store submission

Parallel track: if feedback says users want Laravel Reverb real-time chat, start VPS migration + Reverb install (Phase 3 work per `NEXT_SESSION_PLAN.md`).

## Rolling release cadence

- Bug-fix release: every 2 weeks
- Feature release: monthly
- Major version bump: quarterly

Every release:
1. Release notes in `docs/mobile-app/CHANGELOG.md`
2. Play Store release notes updated
3. In-app release notes banner (dismissible) on next launch

## Next step
→ [step-10-phase-2-close.md](step-10-phase-2-close.md)
