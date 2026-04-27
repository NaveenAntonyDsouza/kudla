# Phase 2b — Week 10: Membership + Razorpay

**Goal:** User can compare plans, apply coupon, pay via Razorpay SDK, see success + receipt. 100%-coupon short-circuit works.

**Design reference:** [`../../design/14-flutter-membership-settings.md §14.1–14.4`](../../design/14-flutter-membership-settings.md)

**Screenshots needed:**
- Plans comparison screen
- Current plan badge
- Checkout bottom sheet (price breakdown + coupon input)
- Razorpay SDK screen (Razorpay's own UI — no design needed)
- Payment success screen
- Payment history list
- Receipt PDF viewer integration

---

## Steps

| # | Step |
|---|------|
| 1 | step-01-plans-screen.md — Card/table comparison |
| 2 | step-02-plan-card-widget.md — Popular / Best Value badges |
| 3 | step-03-checkout-sheet.md — With coupon row + pay button |
| 4 | step-04-coupon-validate-live.md — Debounce + live discount preview |
| 5 | step-05-razorpay-sdk-integration.md — `razorpay_flutter` setup |
| 6 | step-06-create-order-call.md — `POST /membership/order` |
| 7 | step-07-razorpay-open-checkout.md — with prefill |
| 8 | step-08-verify-payment-handler.md — On SUCCESS callback → `/verify` |
| 9 | step-09-free-coupon-short-circuit.md — is_free=true path |
| 10 | step-10-payment-success-screen.md |
| 11 | step-11-receipt-pdf-viewer.md — `url_launcher` to signed URL |
| 12 | step-12-payment-history-screen.md |
| 13 | step-13-failure-states.md — Razorpay ERROR handler + friendly messages |
| 14 | step-14-week-10-acceptance.md |

---

## Acceptance

- [ ] Plans screen loads all active plans from `/membership/plans`
- [ ] Current plan highlighted
- [ ] Coupon `DIAMOND50` shows 50% discount in checkout
- [ ] Pay button opens Razorpay SDK
- [ ] Razorpay success → `/verify` called → membership active → success screen
- [ ] Receipt PDF opens in system PDF viewer
- [ ] 100% coupon skips Razorpay and activates directly
- [ ] Closing Razorpay mid-flow doesn't corrupt state — webhook backs it up
- [ ] Payment failure shows friendly retry screen
- [ ] Payment history shows past subscriptions

**Prerequisites from you before starting this week:**
- [ ] Razorpay test key + secret (share via secure channel, NOT in chat)
- [ ] Admin has configured Razorpay webhook URL in Razorpay dashboard
