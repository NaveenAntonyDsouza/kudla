# Step 7 — Monitoring + Crash Playbook

## Goal
Ongoing operations playbook for the production app.

## Daily (first month)

- [ ] Check Firebase Crashlytics → new crashes
- [ ] Read new Play Store reviews → reply within 24h
- [ ] Check Laravel error log for API 500s
- [ ] Razorpay dashboard → failed payments count

## Weekly

- [ ] DAU / MAU trend
- [ ] Retention curves (D1 / D7 / D30)
- [ ] Crash-free users %
- [ ] Top-3 crashes — investigate + fix
- [ ] Push notification open rate
- [ ] Membership conversion rate (free → paid)

## Monthly

- [ ] Review all P2/P3 bug backlog — pick top 5 to fix
- [ ] Dependency updates — Flutter, Dart, packages
- [ ] Security audit of authenticated endpoints
- [ ] Load test prod API

## Crash response playbook

### P0 (crash affects > 5% of users, or data loss, or payment broken)

1. **Halt rollout** if staged; or pull release if 100%
2. **Diagnose** within 1 hour via Crashlytics stack trace
3. **Fix** in `phase-2-mobile` branch
4. **Test** on physical device
5. **Release hotfix** as `1.0.N` to internal testing
6. **Promote** to 10% rollout after smoke test
7. **Ramp** to 50%, 100% as in steps 4-6

### P1 (crash affects 1-5% or critical UX broken)

Same flow, less urgency — ship within 48h.

### P2 (minor UX issue or edge-case crash)

Queue for next scheduled release (weekly/biweekly cadence post-launch).

## Next step
→ [step-08-user-migration-comms.md](step-08-user-migration-comms.md)
