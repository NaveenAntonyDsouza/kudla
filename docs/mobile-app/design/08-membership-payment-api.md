# 8. Membership & Payment API

Covers: plans list, Razorpay order creation, signature verification, coupon apply, membership status, payment history, webhook.

**Source:** `App\Http\Controllers\MembershipController`, `App\Services\PaymentService`, `App\Models\MembershipPlan`, `App\Models\UserMembership`, `App\Models\Subscription`, `App\Models\Coupon`, `App\Models\CouponUsage`.

**Razorpay Flutter SDK:** `razorpay_flutter` pub.dev package. Android-only for v1 (iOS v2).

---

## 8.1 Data model refresh

Two tables — important to keep straight:

### `subscriptions` (payment audit)
`order_id`, `user_id`, `plan_id`, `amount_paise`, `original_amount_paise`, `discount_amount_paise`, `coupon_id`, `coupon_code`, `status` (pending|paid|failed|refunded), `razorpay_payment_id`, `razorpay_signature`, `created_at`, `paid_at`.

### `user_memberships` (feature access)
`user_id`, `plan_id`, `starts_at`, `ends_at`, `is_active`, `source` (purchase|admin_grant|trial).

**Flow:** subscription row is created when checkout starts. On successful payment, UserMembership row is upserted (extends existing if active, creates new if first-time or expired).

**Prices:** stored as paise (integer). `price_inr` on plans is INR — multiply by 100 when talking to Razorpay.

---

## 8.2 `GET /api/v1/membership/plans`

**Public, no auth required.** Used on onboarding "upgrade" CTA and in membership screen.

### Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "free",
      "title": "Free",
      "description": "Browse profiles, send 5 interests/day",
      "duration_months": 0,
      "original_price_inr": 0,
      "sale_price_inr": 0,
      "daily_interest_limit": 5,
      "view_contacts_limit": 0,
      "daily_contact_views": 0,
      "personalized_messages": false,
      "featured_profile": false,
      "priority_support": false,
      "is_popular": false,
      "is_active": true,
      "features": [
        "Browse unlimited profiles",
        "Send 5 interests/day",
        "Basic search filters"
      ]
    },
    {
      "id": 2,
      "slug": "silver",
      "title": "Silver",
      "description": "3 months premium access",
      "duration_months": 3,
      "original_price_inr": 2999,
      "sale_price_inr": 1999,
      "daily_interest_limit": 10,
      "view_contacts_limit": 50,
      "daily_contact_views": 10,
      "personalized_messages": true,
      "featured_profile": false,
      "priority_support": false,
      "is_popular": false,
      "is_active": true,
      "features": [
        "10 interests/day",
        "View 10 contacts/day (50 total)",
        "Chat with accepted interests",
        "Personalized messages"
      ]
    }
    /* ... Gold, Diamond, Diamond Plus ... */
  ]
}
```

---

## 8.3 `GET /api/v1/membership/me` — Current membership

### Response
```json
{
  "success": true,
  "data": {
    "membership": {
      "plan_id": 3,
      "plan_title": "Gold",
      "starts_at": "2026-02-01T00:00:00Z",
      "ends_at": "2026-08-01T00:00:00Z",
      "is_active": true,
      "days_remaining": 100,
      "source": "purchase",
      "auto_renew": false
    },
    "usage_today": {
      "interests_sent": 4,
      "interests_limit": 20,
      "contacts_viewed": 1,
      "contacts_limit": 10
    }
  }
}
```

**Free user:**
```json
{
  "success": true,
  "data": {
    "membership": {
      "plan_id": 1,
      "plan_title": "Free",
      "is_active": true,
      "days_remaining": null
    },
    "usage_today": { "interests_sent": 3, "interests_limit": 5, "contacts_viewed": 0, "contacts_limit": 0 }
  }
}
```

---

## 8.4 `POST /api/v1/membership/coupon/validate`

Called when user enters a coupon code on checkout screen.

### Request
```json
{ "plan_id": 3, "coupon_code": "DIAMOND50" }
```

### Server-side validation
- Coupon exists and `is_active`
- Not expired (`valid_until >= now()`)
- Started (`valid_from <= now()`)
- Not exceeded `max_usage` across all users
- Not already used by this user (`coupon_usages` lookup)
- Plan eligible (coupon.plan_ids contains plan_id, or coupon is all-plan)
- Min amount satisfied

### Response — valid
```json
{
  "success": true,
  "data": {
    "valid": true,
    "discount_type": "percent",
    "discount_value": 50,
    "original_amount_inr": 5999,
    "discount_amount_inr": 2999,
    "final_amount_inr": 3000,
    "coupon_code": "DIAMOND50"
  }
}
```

### Response — invalid
```json
{
  "success": false,
  "error": {
    "code": "COUPON_INVALID",
    "message": "This coupon has expired."
  }
}
```

---

## 8.5 `POST /api/v1/membership/order` — Create Razorpay order

Creates a `subscriptions` row + Razorpay order server-side, returns order details for Flutter to initialize the Razorpay SDK.

### Request
```json
{
  "plan_id": 3,
  "coupon_code": "DIAMOND50"      // optional
}
```

### Server logic
1. Validate plan exists + is_active
2. Re-validate coupon (exact same checks as §8.4) — don't trust client
3. Compute final amount in paise
4. **100% discount short-circuit:** if final_amount == 0 (coupon applied = 100% off), skip Razorpay and activate membership directly → return success response with `is_free: true` flag
5. Call Razorpay API `orders.create({amount, currency: 'INR', receipt: 'sub_{id}', notes: {user_id, plan_id}})`
6. Create `subscriptions` row with status=pending, razorpay_order_id=rzp response id
7. Increment coupon.usage_count, create coupon_usage row (reverted on payment fail)

### Response (paid flow)
```json
{
  "success": true,
  "data": {
    "is_free": false,
    "subscription_id": 452,
    "razorpay": {
      "order_id": "order_JH5g3kLdN9s",
      "amount_paise": 300000,          // Razorpay uses paise
      "currency": "INR",
      "key": "rzp_live_abcxyz"          // Razorpay key_id (public), from site_settings
    },
    "user": {
      "name": "Naveen D'Souza",
      "email": "naveen@example.com",
      "contact": "9876543210"
    },
    "prefill": {
      "name": "Naveen D'Souza",
      "email": "naveen@example.com",
      "contact": "9876543210"
    },
    "notes": { "subscription_id": 452, "user_id": 42, "plan_id": 3 },
    "theme": { "color": "#dc2626" }
  }
}
```

Flutter initializes Razorpay SDK with this payload → opens native checkout → user pays.

### Response (free flow — 100% coupon)
```json
{
  "success": true,
  "data": {
    "is_free": true,
    "subscription_id": 452,
    "membership": { /* new UserMembership */ }
  }
}
```

---

## 8.6 `POST /api/v1/membership/verify` — Verify payment

Called by Flutter **after** Razorpay SDK returns success callback.

### Request (from Razorpay success handler)
```json
{
  "subscription_id": 452,
  "razorpay_payment_id": "pay_JH5gZnKxY9",
  "razorpay_order_id": "order_JH5g3kLdN9s",
  "razorpay_signature": "abc123..."
}
```

### Server logic
1. Fetch subscription, verify `razorpay_order_id` matches
2. Verify signature: `hash_hmac('sha256', order_id.'|'.payment_id, razorpay_secret)` == signature → else 400 `PAYMENT_FAILED`
3. Call `PaymentService::completeSubscription($subscription, $paymentId)`:
   - Mark subscription `paid`, `paid_at`
   - Create/extend UserMembership (if existing active, extend end date; else create new)
   - `AffiliateTracker::markConversion()` — link original click to this payment
   - Create Transaction record
   - Fire notification + receipt email

### Response 200
```json
{
  "success": true,
  "data": {
    "subscription": {
      "id": 452,
      "status": "paid",
      "amount_inr": 3000,
      "plan_title": "Gold",
      "paid_at": "2026-04-23T15:00:00Z",
      "receipt_url": "https://.../receipt/452.pdf"
    },
    "membership": {
      "plan_id": 3,
      "plan_title": "Gold",
      "starts_at": "2026-04-23T15:00:00Z",
      "ends_at": "2026-10-23T15:00:00Z",
      "is_active": true
    }
  }
}
```

---

## 8.7 `POST /api/v1/webhooks/razorpay` — Webhook

Razorpay pings this URL on payment events. Covers the "user closed app during checkout but payment went through" case.

### Events handled
- `payment.captured` — payment succeeded → idempotently mark subscription paid (same logic as /verify)
- `payment.failed` — mark subscription failed, revert coupon usage if any
- `refund.processed` — mark subscription refunded, deactivate membership

### Security
- Verify `X-Razorpay-Signature` header against `webhook_secret` (from Razorpay dashboard, stored in site_settings or .env)
- Idempotent: check if subscription already marked paid → return 200 no-op

### Response
Always 200 with short body. Razorpay retries on non-2xx, so we want to succeed even on duplicate events.

---

## 8.8 `GET /api/v1/membership/history`

Past payments.

### Query
```
page=1&per_page=10
```

### Response
```json
{
  "success": true,
  "data": [
    {
      "subscription_id": 452,
      "plan_title": "Gold",
      "amount_inr": 3000,
      "original_amount_inr": 5999,
      "discount_amount_inr": 2999,
      "coupon_code": "DIAMOND50",
      "status": "paid",
      "razorpay_payment_id": "pay_JH5gZnKxY9",
      "paid_at": "2026-04-23T15:00:00Z",
      "starts_at": "2026-04-23T15:00:00Z",
      "ends_at": "2026-10-23T15:00:00Z",
      "receipt_url": "https://.../receipt/452.pdf"
    }
  ],
  "meta": { "page": 1, "per_page": 10, "total": 3, "last_page": 1 }
}
```

---

## 8.9 Receipt PDF

PDF receipts generated server-side. URL is `/api/v1/membership/subscriptions/{subscription}/receipt.pdf` — signed URL (Laravel signed routes), no auth header needed (lets Flutter pass URL to system PDF viewer / "Save to Drive" flow).

**Implementation:** generate with `dompdf` or `snappy`. Content: site branding, plan details, amount breakdown, GST note (if applicable), transaction ID, dates. Cached once generated (subscription row references the stored file).

---

## 8.10 Razorpay Flutter SDK integration notes

### Dependencies
```yaml
# pubspec.yaml
dependencies:
  razorpay_flutter: ^1.3.7   # pinned — check latest stable at build time
```

### Flow in Flutter
```dart
// 1. Call /membership/order → get razorpay payload
final order = await api.createOrder(planId: 3, couponCode: 'DIAMOND50');

// 2. Init Razorpay
final rzp = Razorpay();
rzp.on(Razorpay.EVENT_PAYMENT_SUCCESS, (PaymentSuccessResponse r) async {
  // 3. Call /membership/verify
  await api.verifyPayment(
    subscriptionId: order.subscriptionId,
    paymentId: r.paymentId,
    orderId: r.orderId,
    signature: r.signature,
  );
});
rzp.on(Razorpay.EVENT_PAYMENT_ERROR, (PaymentFailureResponse r) {
  // Show error UI — server will get webhook if payment actually went through
});

// 4. Open checkout
rzp.open({
  'key': order.razorpay.key,
  'order_id': order.razorpay.orderId,
  'amount': order.razorpay.amountPaise,
  'name': 'Kudla Matrimony',
  'description': 'Gold Plan — 6 months',
  'prefill': order.prefill.toMap(),
  'theme': order.theme.toMap(),
});
```

### UPI apps
Razorpay SDK handles UPI intents, NetBanking, cards, wallets automatically on Android. No extra config.

### Play Store policy
Google Play forbids third-party payment SDKs for **digital goods**. Matrimony memberships fall into the "matrimonial / dating services" category which is explicitly allowed to use direct payment (Razorpay, etc.) — not required to use Google Play Billing. Confirm in Play Store console policies at submit time.

---

## 8.11 GST handling

Current web system stores amounts inclusive of GST. For v1, same approach — invoice shows `Amount (incl. GST)`. No tax line item separation.

Phase 3 enhancement: add `gst_amount_paise` column, split invoices, surface for B2B customers.

---

## 8.12 Build Checklist

- [ ] `App\Http\Controllers\Api\V1\MembershipController`:
  - [ ] `plans()` — public, list active plans
  - [ ] `mine()` — current membership + usage
  - [ ] `validateCoupon()` — no side effects
  - [ ] `createOrder()` — Razorpay order, 100%-discount short-circuit
  - [ ] `verifyPayment()` — signature check + activation
  - [ ] `history()` — past subscriptions
  - [ ] `webhook()` — Razorpay event handler
- [ ] `App\Http\Resources\V1\MembershipPlanResource`, `UserMembershipResource`, `SubscriptionResource`
- [ ] Extend `PaymentService` if needed (already has most logic)
- [ ] Receipt PDF generation service
- [ ] Webhook signature verification middleware
- [ ] Scheduled job `memberships:deactivate-expired` (already exists per docs)
- [ ] Pest tests:
  - [ ] Coupon validate (valid + expired + max-usage + not-eligible-plan)
  - [ ] Order creation with 100% discount → skips Razorpay
  - [ ] Signature verification rejects tampered signature
  - [ ] Webhook idempotent on duplicate event

**Acceptance:**
- Flutter can go from plans screen → checkout → Razorpay SDK → payment → verify → active membership
- Receipt PDF downloads correctly via signed URL
- Webhook handles late `payment.captured` that arrives after user closed the app
- 100% coupon activates membership without opening Razorpay
