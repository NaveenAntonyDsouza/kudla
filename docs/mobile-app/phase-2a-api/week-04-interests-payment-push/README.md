# Phase 2a — Week 4: Interests, Payment, Push

**Goal:** complete interest lifecycle + chat polling + Razorpay integration + all engagement endpoints (notifications, shortlist, views, block, report, ignore, id-proof, settings, static pages, success stories, contact form) + FCM push infrastructure.

**Design reference:** [`design/07-interests-chat-api.md`](../../design/07-interests-chat-api.md), [`design/08-membership-payment-api.md`](../../design/08-membership-payment-api.md), [`design/09-engagement-api.md`](../../design/09-engagement-api.md), [`design/10-push-notifications.md`](../../design/10-push-notifications.md)

**Prerequisite:** [Week 3 acceptance](../week-03-profiles-photos-search/week-03-acceptance.md) ✅

---

## Steps

| # | Step | Status |
|---|------|--------|
| 1 | [Interest endpoints — inbox + send + accept/decline/cancel/star/trash](step-01-interest-endpoints.md) | ☐ |
| 2 | [Chat polling endpoint — GET /interests/{id}/messages/since](step-02-chat-polling.md) | ☐ |
| 3 | [Membership plans + my membership + coupon validation](step-03-membership-plans-coupon.md) | ☐ |
| 4 | [Razorpay create order + verify payment](step-04-razorpay-order-verify.md) | ☐ |
| 5 | [Razorpay webhook endpoint](step-05-razorpay-webhook.md) | ☐ |
| 6 | [FCM setup — install `kreait/laravel-firebase`](step-06-fcm-install.md) | ☐ |
| 7 | [Extend NotificationService with push dispatch + quiet hours](step-07-notification-push-dispatch.md) | ☐ |
| 8 | [Notification endpoints (list, mark read, unread-count)](step-08-notification-endpoints.md) | ☐ |
| 9 | [Shortlist + views endpoints](step-09-shortlist-views.md) | ☐ |
| 10 | [Block + report + ignore endpoints](step-10-block-report-ignore.md) | ☐ |
| 11 | [ID proof upload endpoints](step-11-id-proof.md) | ☐ |
| 12 | [Settings endpoints (visibility/alerts/password/hide/delete)](step-12-settings.md) | ☐ |
| 13 | [Contact form + static pages + success stories](step-13-engagement-public.md) | ☐ |
| 14 | [Onboarding endpoints (4 optional steps)](step-14-onboarding-endpoints.md) | ☐ |
| 15 | [Bruno test collection + load test](step-15-bruno-load-test.md) | ☐ |

**End-of-week acceptance:** [week-04-acceptance.md](week-04-acceptance.md) (also Phase 2a exit gate)

---

## End-of-phase state

By end of Week 4, the API layer is **feature-complete**. Every Flutter screen in Phase 2b has its backend waiting.

**Around 80 endpoints total live at `/api/v1/*`.**

---

**Start:** [Step 1 — Interest endpoints →](step-01-interest-endpoints.md)
