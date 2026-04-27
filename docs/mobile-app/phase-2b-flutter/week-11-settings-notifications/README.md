# Phase 2b — Week 11: Settings + Notifications + Push

**Goal:** Settings tree complete. Push notifications fully working: arrive in tray, tap to deep-link, quiet hours respected.

**Design reference:** [`../../design/14-flutter-membership-settings.md §14.5–14.13`](../../design/14-flutter-membership-settings.md), [`../../design/10-push-notifications.md`](../../design/10-push-notifications.md)

**Screenshots needed:**
- Settings root (list view with sections)
- Visibility settings
- Alerts settings (email + push tabs)
- Change password
- Hide profile confirmation
- Delete account (2 steps: reason + confirm)
- ID proof upload (status card + form)
- Contact / FAQ / About
- Notifications list (bell icon screen)
- In-app notification banner (foreground)

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-settings-root.md — List view with sections |
| 2 | step-02-visibility-sub-screen.md |
| 3 | step-03-alerts-sub-screen.md — Email + push tabs |
| 4 | step-04-change-password-screen.md |
| 5 | step-05-hide-toggle-inline.md |
| 6 | step-06-delete-account-flow.md — 2-step with password confirm |
| 7 | step-07-id-proof-screen.md |
| 8 | step-08-contact-screen.md |
| 9 | step-09-static-pages-viewer.md — flutter_html for FAQ / privacy |
| 10 | step-10-fcm-init-permissions.md — Request `POST_NOTIFICATIONS` + register token |
| 11 | step-11-notification-handlers.md — onMessage, onMessageOpenedApp, getInitialMessage |
| 12 | step-12-notifications-screen.md — List + mark read |
| 13 | step-13-notification-badge.md — `flutter_app_badger` + bottom nav badge |
| 14 | step-14-biometric-toggle.md |
| 15 | step-15-week-11-acceptance.md |

---

## Acceptance

- [ ] Settings root shows all 7 sections
- [ ] Visibility toggles save to backend
- [ ] Alerts toggles correctly split email vs push
- [ ] Change password → other devices logged out → inline confirmation
- [ ] Hide profile → confirm dialog → profile hidden from search
- [ ] Delete account → 2-step flow → soft-deleted + logged out
- [ ] ID proof upload → pending state shown
- [ ] Contact form submits + email arrives at admin
- [ ] Push notification arrives with app foregrounded → in-app banner
- [ ] Push notification arrives with app backgrounded → system tray
- [ ] Tap tray notification → app opens to correct screen
- [ ] Terminated app + push notification tap → cold start to correct screen
- [ ] Notification bell badge count updates after new push
- [ ] Biometric toggle enables/disables correctly
