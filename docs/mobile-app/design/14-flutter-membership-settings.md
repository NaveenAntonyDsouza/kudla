# 14. Flutter — Membership & Settings Screens

Covers: membership plans, Razorpay checkout, receipt, settings (visibility, alerts, password, hide, delete), ID proof upload, biometric toggle, about / help / contact.

---

## 14.1 Membership Plans Screen

**Purpose:** compare plans + start checkout.

**Entry points:**
- Dashboard CTA "Upgrade to Gold"
- Profile view "Contact locked — Upgrade"
- Search results "Premium profiles" banner
- Chat thread "Chat requires premium"
- Deep link `/membership`

**UI layout:**
- Top: current plan badge + expiry
- Comparison table or card list:
  - Plan badge (Popular, Best Value tags)
  - Plan name + tagline
  - Price (strike-through original + sale price)
  - Duration
  - Feature list (interests/day, contacts/day, chat, personalized messages, featured profile, priority support)
  - "Select" button → checkout sheet
- Bottom: "Got a coupon?" link opens coupon input row

**API calls:**
- `GET /api/v1/membership/plans` (public, no auth needed for browsing)
- `GET /api/v1/membership/me` (current plan + usage)
- `POST /api/v1/membership/coupon/validate` (when user taps "Apply coupon")

**State:**
- `plansProvider` — plan list
- `currentMembershipProvider` — current plan
- `selectedPlanProvider` — user's selection
- `appliedCouponProvider` — coupon validation result

**Navigation out:**
- "Select" → opens checkout bottom sheet (`/membership/checkout`)

**Empty / error:** standard.

**Design placeholder:** plan comparison screen screenshot.

---

## 14.2 Checkout Sheet

**Purpose:** confirm plan + coupon, then open Razorpay.

**Entry:** plans screen "Select" button.

**UI:**
- Title: "Upgrade to Gold"
- Price breakdown: original amount, discount (if coupon), final amount
- Coupon input (with "Apply" button) — live validates via API
- Pay button: "Pay ₹1999" (updates with final amount)
- Terms acknowledgement: "By proceeding, you accept the Refund Policy [link]"
- Support footer: "Payment issues? Call support: 1234567890"

**API calls:**
- `POST /api/v1/membership/coupon/validate` on coupon input
- `POST /api/v1/membership/order` on Pay tap — returns Razorpay payload
- Razorpay SDK opens native checkout
- `POST /api/v1/membership/verify` on SDK success callback

**Flow:**
1. User enters coupon → validate in real-time
2. Tap "Pay" → create order server-side
3. If `is_free: true` (100% coupon) → skip Razorpay, show success directly
4. Else → `Razorpay.open(...)` with returned payload
5. Razorpay callbacks:
   - `EVENT_PAYMENT_SUCCESS` → call `/verify` → show receipt screen
   - `EVENT_PAYMENT_ERROR` → show failure screen (webhook will save state server-side if it was genuine)
   - `EVENT_EXTERNAL_WALLET` → no action (user switched to another wallet, Razorpay handles)

**Razorpay SDK init snippet:**
```dart
final rzp = Razorpay();
rzp.on(Razorpay.EVENT_PAYMENT_SUCCESS, _onSuccess);
rzp.on(Razorpay.EVENT_PAYMENT_ERROR, _onError);
rzp.on(Razorpay.EVENT_EXTERNAL_WALLET, _onExternalWallet);
rzp.open({
  'key': order.razorpay.key,
  'order_id': order.razorpay.orderId,
  'amount': order.razorpay.amountPaise,
  'name': 'Kudla Matrimony',
  'description': '${plan.title} — ${plan.durationMonths} months',
  'prefill': {
    'name': user.name,
    'email': user.email,
    'contact': user.phone,
  },
  'theme': { 'color': theme.primaryColor },
  'notes': { 'subscription_id': order.subscriptionId.toString() },
});

// Cleanup
@override
void dispose() {
  rzp.clear();
  super.dispose();
}
```

**State:**
- `checkoutStateProvider` — enum: idle / creatingOrder / razorpayOpen / verifying / success / error
- UI reacts to each state

**Error handling:**
- Network failure before `/order` → retry button
- Razorpay error callback → "Payment failed or cancelled" + retry button
- `/verify` signature mismatch → "Verification failed — we'll email if payment is confirmed" (webhook fallback)

**Design placeholder:** checkout sheet with price breakdown + coupon row.

---

## 14.3 Payment Success Screen

**Purpose:** confirm activation + next steps.

**Entry:** checkout sheet `/verify` success.

**UI:**
- Green check animation
- "You're now Gold!"
- Plan details: plan name, duration, expires-on date
- CTA: "Start exploring matches" → dashboard
- Secondary: "Download receipt" → opens signed URL in system PDF viewer

**API calls:** none (data from `/verify` response).

**Design placeholder:** success screen.

---

## 14.4 Payment History Screen

**Purpose:** past transactions + receipts.

**Entry:** Settings → "Payment history" menu.

**UI:**
- List of subscriptions:
  - Date, plan, amount
  - Status chip (Paid / Failed / Refunded)
  - "Receipt" link → opens PDF
- Pagination

**API calls:** `GET /api/v1/membership/history`.

**Empty:** "No past payments. Upgrade to unlock premium features." → dashboard.

---

## 14.5 Settings Screen (root)

**Purpose:** main settings hub.

**Entry:** bottom nav "More" → Settings, or deep link `/settings`.

**UI layout (list of rows):**

### Account
- Name (view only — edit via profile)
- Email + verification badge
- Phone + verification badge
- Matri ID (copyable)
- Change password → opens password screen

### Notifications
- Email alerts → opens alerts screen
- Push notifications → opens push screen
- Quiet hours → opens quiet hours setter

### Privacy
- Profile visibility → opens visibility screen
- Photo privacy → opens photo privacy screen (also accessible from Photo Manager)
- Hide profile (toggle inline)
- Who can see contact (inline: premium only / everyone)

### Verification
- Email (verified ✓ or "Verify now")
- Phone (verified ✓ or "Verify now")
- ID Proof (pending/verified/rejected/not submitted) → opens ID proof screen

### App
- Biometric login (toggle if device supports)
- Language (locked to English v1, future phase)
- Theme (locked to system, future phase)
- App version
- Clear cache

### Support
- Contact us → opens contact form
- FAQ → opens webview (reuses static page)
- Rate the app → launches Play Store intent
- Share the app → system share sheet

### Legal
- Privacy Policy → static page
- Terms of Service → static page
- Refund Policy → static page
- Child Safety → static page

### Account Actions (danger zone)
- Logout → confirmation → calls `/auth/logout` + clears tokens + routes to login
- Delete account → opens delete flow

**API calls:**
- `GET /api/v1/settings` on mount — single fetch for all values

**State:** `settingsProvider`. Each edit sub-screen invalidates on save.

**Design placeholder:** settings list.

---

## 14.6 Visibility Settings

**Fields (toggles & radio):**
- "Who can see my profile?" radio:
  - Everyone (default)
  - Premium members only
  - High-match profiles only (score ≥ 70)
- "Show my profile only to same religion?" toggle
- "Show my profile only to same denomination?" toggle (Christian only)
- "Show my profile only to same mother tongue?" toggle

**API calls:** `PUT /api/v1/settings/visibility`.

---

## 14.7 Alerts Settings

**Sections: Email / Push**

Each section has toggles for:
- New interests
- Interest accepted
- Interest declined
- Profile views
- New matches
- Photo requests
- Promotions & updates

API: `PUT /api/v1/settings/alerts` with only changed keys.

---

## 14.8 Change Password Screen

**Fields:** current, new, confirm.

**API:** `PUT /api/v1/settings/password`.

**Side effect:** other devices get logged out — show confirmation: "All other devices logged out. Sign back in on each to continue."

---

## 14.9 Hide Profile Toggle

Not a screen — inline toggle on Settings root. Server call:
- Enable: `POST /api/v1/settings/hide`
- Disable: `POST /api/v1/settings/unhide`

When hiding, show confirmation modal: "Your profile will be hidden from search. You can still access interests and chats. Unhide any time."

---

## 14.10 Delete Account Flow

**Step 1: Reason screen**
- Radio: Found partner / Poor experience / Not interested anymore / Other
- Optional free-text feedback

**Step 2: Confirmation**
- Warning: "This will hide your profile and schedule permanent deletion in 30 days. You can reactivate by logging in within that window."
- Re-enter password
- "Delete my account" button (red)

**API:** `POST /api/v1/settings/delete` with `{password, reason, feedback}`.

**Post-delete:** clears all local storage, navigates to "Account deleted" screen with "Sign up again" CTA.

---

## 14.11 ID Proof Screen

**Purpose:** upload government ID for verification badge.

**Entry:** Settings → Verification → ID Proof.

**UI:**
- If submitted: show current status (pending / verified / rejected)
- If rejected: show reason + re-upload option
- If verified: show green badge + "Valid until..." (if applicable)
- Upload form:
  - Document type (dropdown: Aadhaar / Passport / Voter ID / Driving License / PAN)
  - Document number (masked, per-type format)
  - Front side (file picker — camera or gallery)
  - Back side (optional, some docs don't have back)
  - Submit button

**API calls:**
- `GET /api/v1/id-proof` on mount
- `POST /api/v1/id-proof` on submit (multipart)
- `DELETE /api/v1/id-proof/{id}` if user wants to withdraw

**Privacy copy:** "Your ID is visible only to our verification team and is encrypted at rest. It's never shown on your profile."

**Design placeholder:** status card + upload form.

---

## 14.12 About / Help / Contact Screens

### About
- Logo + app name + version + tagline
- Credits
- Link to website
- Back

### FAQ
- Uses `GET /api/v1/static-pages/faq` (if exists) — renders with `flutter_html`
- Or falls back to Blade-style FAQ rendered server-side via webview

### Contact Us
- Form fields: name, email, phone, subject, message
- Submit → `POST /api/v1/contact`
- Success: "Thanks! We'll reply within 24 hours."

### Support
- WhatsApp button: `url_launcher('https://wa.me/919876543210?text=Hi')`
- Call button: `url_launcher('tel:+918241234567')`
- Email button: `url_launcher('mailto:support@...')`

**API:** `GET /site/settings` provides these contact numbers. No extra calls needed.

---

## 14.13 Biometric Toggle

Shown on Settings → App section.

**If device supports + token valid:**
- Toggle: "Sign in with fingerprint/face"
- Enable flow: same as §12.13 enrolment (authenticate to confirm, store flag)
- Disable flow: confirm dialog + clear flag

**If device doesn't support:** row hidden.

**State:** `biometricEnabledProvider` backed by secure storage.

---

## 14.14 Active Sessions (future, not v1)

Lists active devices (`GET /api/v1/auth/devices` endpoint — add in Phase 3) with Revoke buttons per row. Useful for compromised-device cleanup.

v1: single "Log out of all other devices" button suffices (implemented via password change endpoint or a dedicated new endpoint).

---

## 14.15 Build Checklist

- [ ] `MembershipScreen` with plan comparison + current badge
- [ ] `CheckoutSheet` with Razorpay integration + coupon validation
- [ ] `PaymentSuccessScreen` with download receipt
- [ ] `PaymentHistoryScreen`
- [ ] `SettingsScreen` root with all sections
- [ ] `VisibilitySettingsScreen`
- [ ] `AlertsSettingsScreen`
- [ ] `ChangePasswordScreen`
- [ ] `HideProfileConfirmDialog` (inline toggle action)
- [ ] `DeleteAccountFlow` (2-step)
- [ ] `IdProofScreen` with status card + upload form
- [ ] `AboutScreen`, `FaqScreen`, `ContactScreen`, `SupportActions` (WhatsApp/call/email shortcuts)
- [ ] Biometric toggle on Settings root
- [ ] Deep links from dashboard CTAs work correctly (membership, verify, id-proof)

**Screens needing screenshots:**
1. Membership plans comparison
2. Checkout sheet
3. Payment success
4. Payment history
5. Settings root
6. Visibility settings
7. Alerts settings (email tab + push tab)
8. Change password
9. Delete account (reason step + confirm step)
10. ID proof (pending state + rejected state + verified state)
11. About / FAQ / Contact
12. Hide profile confirmation dialog

**Acceptance:**
- Full Razorpay flow from plans screen → checkout → success → receipt
- 100% coupon short-circuits Razorpay and activates directly
- All settings toggles persist via API and re-read correctly on next screen open
- Delete account soft-deletes, logs out, clears storage
- ID proof upload → pending → admin verifies → screen shows verified state
