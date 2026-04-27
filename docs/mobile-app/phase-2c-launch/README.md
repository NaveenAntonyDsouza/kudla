# Phase 2c — Play Store Launch

**Duration:** 4 weeks
**Goal:** safely ship the Flutter app to production via Play Store staged rollout. Monitor, iterate, scale to 100%.

**Design reference:** [`../design/15-flutter-polish-launch.md §15.11`](../design/15-flutter-polish-launch.md)

**Prerequisite:** [Phase 2b week-12 acceptance](../phase-2b-flutter/week-12-polish/README.md) ✅ (signed AAB uploaded to internal testing)

---

## Rollout stages

| Stage | Duration | Rollout % | Purpose |
|-------|----------|-----------|---------|
| Internal testing | 1 week | 100% of 10 internal testers | Smoke-test every flow + fix blockers |
| Closed testing | 2 weeks | 100% of 50 invited beta users | Real usage feedback, fix rough edges |
| Production 10% | 3 days | 10% of existing installs | Monitor crash rate, early reviews |
| Production 50% | 3 days | 50% | Half the user base on new app |
| Production 100% | ongoing | Everyone | Full rollout |

---

## Steps

| # | Step | Status |
|---|------|--------|
| 1 | [Pre-launch checklist](step-01-pre-launch-checklist.md) | ☐ |
| 2 | [Internal testing track setup](step-02-internal-testing.md) | ☐ |
| 3 | [Closed testing cohort + feedback loop](step-03-closed-testing.md) | ☐ |
| 4 | [Staged production rollout — 10%](step-04-production-10-percent.md) | ☐ |
| 5 | [Staged rollout — 50%](step-05-production-50-percent.md) | ☐ |
| 6 | [Full rollout — 100%](step-06-production-100-percent.md) | ☐ |
| 7 | [Monitoring + crash playbook](step-07-monitoring-playbook.md) | ☐ |
| 8 | [User migration communication](step-08-user-migration-comms.md) | ☐ |
| 9 | [Post-launch iteration plan](step-09-post-launch-iteration.md) | ☐ |
| 10 | [Phase 2 close + Phase 3 kickoff plan](step-10-phase-2-close.md) | ☐ |

---

## Success metrics

Before Phase 2c is considered "done":

- [ ] Crash-free rate ≥ 99%
- [ ] Play Store rating ≥ 4.2 from ≥ 30 reviews
- [ ] DAU ≥ 50% of existing webview DAU (user migration successful)
- [ ] Zero P0 bugs open
- [ ] < 5 P1 bugs open
- [ ] Average session length ≥ 4 minutes (webview was < 2)
- [ ] Push notification open rate ≥ 20%

---

## Rollback plan

If something goes wrong at any rollout stage:

1. **Pause staged rollout** in Play Console (freezes at current %)
2. **Diagnose** via Crashlytics + user reports (24h window)
3. **Hotfix** in `phase-2-mobile` branch → new build → new AAB
4. **Upload + resume** staged rollout from start

**Halting triggers:**
- Crash-free rate drops below 98%
- P0 bug reported (payment fails, auth broken, data loss)
- Play Store rating dips below 4.0

---

**Start:** [Step 1 — Pre-launch checklist →](step-01-pre-launch-checklist.md)
