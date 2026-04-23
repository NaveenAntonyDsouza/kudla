# Phase 2b — Week 4: Dashboard + Profile View

**Goal:** Two biggest screens — Dashboard (landing after login) and Profile View (conversion surface).

**Design reference:** [`../../design/13-flutter-core-screens.md §13.1, 13.6`](../../design/13-flutter-core-screens.md)

**Screenshots needed:**
- Dashboard (full, with section ordering)
- Profile View (hero + each tab: About / Family / Preferences / Background / Contact)
- ProfileCard widget (all 3 variants: full, compact, inbox)
- Gallery (full-screen photo viewer)
- Sticky action bar at bottom of profile

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-profile-card-widget.md — Shared `ProfileCard` with 3 variants + blurred photo + badges |
| 2 | step-02-dashboard-shimmer.md — Skeleton loader matching dashboard layout |
| 3 | step-03-dashboard-sections.md — Build each of 7 sections (CTA/stats/carousels) |
| 4 | step-04-dashboard-cta-logic.md — Conditional CTA per user state (complete-profile/verify/upgrade) |
| 5 | step-05-horizontal-carousel-widget.md — Shared carousel with "See all →" |
| 6 | step-06-discover-teasers.md — 2×2 tile grid |
| 7 | step-07-profile-view-shell.md — Hero + tabbed body scaffold |
| 8 | step-08-profile-view-tabs.md — About / Family / Preferences / Background tabs |
| 9 | step-09-profile-view-gallery.md — Photo carousel + full-screen viewer |
| 10 | step-10-profile-action-bar.md — Sticky bottom bar (Send Interest / Shortlist / Share / •••) |
| 11 | step-11-profile-gated-states.md — Blurred photos + premium CTA + hidden contact |
| 12 | step-12-dashboard-pull-to-refresh.md |
| 13 | step-13-week-04-integration-test.md |
| 14 | week-04-acceptance.md |

---

## Acceptance

- [ ] Dashboard loads in < 2s on good network
- [ ] All 7 sections render with shimmer fallback
- [ ] Empty sections ("no mutual matches yet") show helpful state, not error
- [ ] Tap profile card → opens Profile View
- [ ] Profile View shows 4 tabs, swipe between works
- [ ] Non-premium viewer sees blurred photos + hidden contact
- [ ] Share button opens system share sheet with link
- [ ] Pull-to-refresh reloads dashboard
- [ ] Offline banner appears when network drops
