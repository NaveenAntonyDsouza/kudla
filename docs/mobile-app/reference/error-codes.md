# API Error Codes

Every response where `success: false` has an `error.code`. Flutter switches on these codes.

| Code | HTTP | When | Flutter UX | Retry? |
|------|------|------|-----------|--------|
| `VALIDATION_FAILED` | 422 | FormRequest fails | Inline field errors via `error.fields` | No — user fixes input |
| `UNAUTHENTICATED` | 401 | Missing/invalid/expired token | Clear token, route to login | Yes via re-login |
| `UNAUTHORIZED` | 403 | Action forbidden (e.g. free user hits chat) | Modal dialog with upgrade CTA if appropriate | Depends |
| `NOT_FOUND` | 404 | Resource missing OR intentionally masked (blocked, hidden) | "Profile not available" message, back button | No |
| `METHOD_NOT_ALLOWED` | 405 | Wrong HTTP verb | Shouldn't happen in prod — bug | No |
| `GENDER_MISMATCH` | 403 | Cannot interact with same-gender | Server-side guard — shouldn't show in UI | No |
| `SELF_ACTION` | 403 | Cannot block/report/interest self | Guard — shouldn't show in UI | No |
| `DAILY_LIMIT_REACHED` | 429 | Interest daily cap | Upgrade dialog with "{limit}/{used} today, resets at {time}" | At midnight |
| `ALREADY_EXISTS` | 409 | Duplicate interest, duplicate photo request | "You already have an open request" snackbar | No |
| `OTP_INVALID` | 422 | Wrong OTP | Shake input, "Invalid code, try again" | Yes, re-enter |
| `OTP_EXPIRED` | 422 | OTP expired | Auto-trigger new send, show "new code sent" | Yes via new send |
| `OTP_COOLDOWN` | 429 | Resent too soon | Show countdown timer, disable send button | Wait for cooldown |
| `PROFILE_INCOMPLETE` | 403 | Endpoint requires completed onboarding | Route to appropriate onboarding step | Yes after completion |
| `PROFILE_SUSPENDED` | 403 | User is suspended/banned | Blocking screen with support contact | No |
| `PAYMENT_FAILED` | 400 | Razorpay signature verification failed | Retry dialog + support contact | Yes (new order) |
| `COUPON_INVALID` | 400 | Bad / expired / exhausted coupon | Inline message on coupon input | Yes with different coupon |
| `PREMIUM_REQUIRED` | 403 | Free user hit premium feature | Upgrade dialog | Yes after upgrade |
| `THROTTLED` | 429 | Rate limit hit | "Too many requests, try again in {n}s" | Yes after wait |
| `SERVER_ERROR` | 500 | Unhandled exception | Generic error view + Crashlytics report | Sometimes |

## Adding a new error code

1. Add to this table
2. Add to `App\Exceptions\ApiExceptionHandler` switch statement
3. Update Flutter `ApiException` mapping if special handling needed
4. Add Pest test asserting the code surfaces correctly

## Flutter side

Each code maps to a specific UX pattern. Example snippet:

```dart
Widget errorFor(ApiException e) {
  return switch (e.code) {
    'VALIDATION_FAILED' => ValidationErrorInline(fields: e.fields ?? {}),
    'UNAUTHENTICATED' => SizedBox.shrink(),  // interceptor already routed to /login
    'DAILY_LIMIT_REACHED' => DailyLimitDialog(),
    'PREMIUM_REQUIRED' => UpgradeDialog(),
    'COUPON_INVALID' => SnackBar(content: Text(e.message)),
    'PAYMENT_FAILED' => PaymentRetryDialog(),
    'THROTTLED' => ThrottledBanner(message: e.message),
    _ => GenericErrorView(message: e.message),
  };
}
```
