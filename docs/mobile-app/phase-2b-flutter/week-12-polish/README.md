# Phase 2b — Week 12: Polish + Release Build

**Goal:** App shell, bottom nav, offline cache, share card, error states, accessibility pass, signed release AAB, Play Store assets.

**Design reference:** [`../../design/15-flutter-polish-launch.md`](../../design/15-flutter-polish-launch.md)

**Screenshots needed:**
- Bottom nav (5 tabs)
- "More" menu (overflow items)
- Offline banner
- Pull-to-refresh indicator
- Share card image design (for generation)
- Error view / empty states / update required screen
- Loading skeleton screens

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-app-shell-bottom-nav.md — StatefulShellRoute with 5 tabs |
| 2 | step-02-more-menu-screen.md |
| 3 | step-03-pull-to-refresh-wrapper.md — Applied to all list screens |
| 4 | step-04-shimmer-skeletons.md |
| 5 | step-05-offline-banner.md — `connectivity_plus` listener |
| 6 | step-06-hive-cache-layer.md — Site config + reference data + last dashboard |
| 7 | step-07-share-card-design.md — RepaintBoundary render + share_plus |
| 8 | step-08-empty-error-widgets.md — Shared UI library |
| 9 | step-09-update-required-screen.md — Triggers on min_version mismatch |
| 10 | step-10-maintenance-screen.md — Triggers on 503 |
| 11 | step-11-accessibility-pass.md — Tooltip labels, contrast, 48dp touch targets |
| 12 | step-12-analytics-crashlytics.md — Firebase Crashlytics + optional Analytics |
| 13 | step-13-proguard-rules.md — Razorpay + Firebase keep rules |
| 14 | step-14-keystore-generate.md — Release keystore + key.properties |
| 15 | step-15-release-build.md — `flutter build appbundle --release --dart-define=FLAVOR=prod` |
| 16 | step-16-play-store-listing-assets.md — Icon / feature graphic / screenshots |
| 17 | step-17-play-console-upload.md — Upload AAB to internal testing track |
| 18 | step-18-week-12-acceptance.md — Phase 2b exit gate |

---

## Acceptance (Phase 2b exit)

- [ ] All 5 bottom nav tabs work with correct screens
- [ ] Pull-to-refresh works on all list screens
- [ ] Offline banner appears + hides correctly
- [ ] Share card renders on device (test with actual profile data)
- [ ] Accessibility: TalkBack can navigate login + dashboard flows
- [ ] Release AAB builds without errors
- [ ] Release build installed on physical device works correctly (test full journey)
- [ ] Crashlytics initialized + test crash visible in console
- [ ] Play Store assets ready:
  - [ ] 512×512 icon
  - [ ] 1024×500 feature graphic
  - [ ] 8 phone screenshots 1080×1920
  - [ ] Short description (80 chars)
  - [ ] Full description (4000 chars)
  - [ ] Privacy policy URL
  - [ ] Content rating completed
  - [ ] Data safety form completed
- [ ] Internal testing track populated with AAB

**Next:** [phase-2c-launch/](../../phase-2c-launch/README.md) — rollout to users

**Phase 2b complete ✅**
