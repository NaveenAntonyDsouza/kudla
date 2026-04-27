# API Error Codes

Every response where `success: false` carries an `error.code`. Flutter switches on these codes to drive UX. **This document is authoritative** — kept in sync with the codebase via `tests/Tools/openapi-validate.php` and the Pest contract suite.

## Global / framework codes

These come from `App\Exceptions\ApiExceptionHandler` and apply to every endpoint.

| Code | HTTP | When | Flutter UX | Retry? |
|------|------|------|-----------|--------|
| `VALIDATION_FAILED` | 422 | Any FormRequest validation fails | Inline field errors via `error.fields` | No — user fixes input |
| `UNAUTHENTICATED` | 401 | Missing / invalid / expired token | Clear token, route to login | Yes via re-login |
| `UNAUTHORIZED` | 403 | Authorization gate fails (Laravel `Gate::denies`) | Snackbar or modal with action context | Depends |
| `NOT_FOUND` | 404 | Resource missing OR intentionally masked (blocked, hidden) | "Profile not available" message, back button | No |
| `METHOD_NOT_ALLOWED` | 405 | Wrong HTTP verb | Shouldn't happen in prod — bug | No |
| `THROTTLED` | 429 | Rate limit hit (`throttle:N,M` middleware) | "Too many requests, try again in {n}s" | Yes after wait |
| `SERVER_ERROR` | 500 | Unhandled exception | Generic error view + Crashlytics report | Sometimes |

## Auth + OTP

| Code | HTTP | When | Flutter UX | Retry? |
|------|------|------|-----------|--------|
| `OTP_INVALID` | 422 | OTP code mismatched, expired, or never sent | Shake input, "Invalid or expired code, try again" | Yes, re-enter or resend |
| `ALREADY_VERIFIED` | 422 | Phone/email already verified for the user | Skip verification step, route forward | No |
| `PROFILE_REQUIRED` | 422 | Endpoint needs a Profile but the User doesn't have one | Route to profile-creation step (registration step-1) | Yes after creation |
| `PROFILE_SUSPENDED` | 403 | User account flagged by admin | Blocking screen with support contact | No |

OTP throttling: resend cooldown enforced at the route level via `throttle:5,1` (5 sends per minute per IP) — fires `THROTTLED` (see Global table), not a dedicated `OTP_COOLDOWN`. The expiration vs. wrong-code distinction is collapsed into `OTP_INVALID` since `OtpService::verify()` returns a single boolean. Splitting into `OTP_EXPIRED` + `OTP_COOLDOWN` is filed as a Phase 2c UX-polish task.

## Profile / interaction guards

Anti-enumeration + business-rule guards on cross-profile actions (interest, photo-request, block, report, shortlist, ignore, message).

| Code | HTTP | When | Flutter UX | Retry? |
|------|------|------|-----------|--------|
| `GENDER_MISMATCH` | 403 | Same-gender interaction attempt | Server-side guard — shouldn't show in normal UI | No |
| `INVALID_TARGET` | 422 | Target is self, blocked, or otherwise ineligible (block/report/shortlist/ignore) | Snackbar carrying the service message | No |
| `SELF_REQUEST` | 422 | Photo-request flow specifically — user pointed at own profile | Same as INVALID_TARGET | No |
| `INVALID_INTEREST` | 422 | Generic interest-service failure (block, premium-gate, duplicate, accept/decline state) | Snackbar carrying the service message | No |
| `DAILY_LIMIT_REACHED` | 429 | Interest daily cap exceeded for sender's plan | Upgrade dialog with `{limit}/day, used {used}` | At midnight (or via plan upgrade) |
| `ALREADY_EXISTS` | 409 | Duplicate resource (interest, photo-request) | "You already have an open request" snackbar | No |
| `CANCEL_WINDOW_EXPIRED` | 422 | Interest cancel attempted after the 24-hour window | "This interest can no longer be cancelled" message | No |
| `LOW_MATCH_SCORE` | 403 | Match-score-gated endpoint with a score below the floor | Modal explaining the score gate | Depends |
| `PREFERENCES_REQUIRED` | 422 | Match endpoint called before partner-preferences saved | Route to partner-preferences onboarding step | Yes after saving |
| `PREMIUM_REQUIRED` | 403 | Free user hit a premium-only feature | Upgrade dialog | Yes after upgrade |

## Payment / membership

| Code | HTTP | When | Flutter UX | Retry? |
|------|------|------|-----------|--------|
| `COUPON_INVALID` | 400 | Bad / expired / exhausted / non-applicable coupon | Inline message on coupon input | Yes with different coupon |
| `SIGNATURE_INVALID` | 422 | Razorpay (or other gateway) callback signature failed verification | Retry dialog + support contact | Yes (new order) |
| `GATEWAY_NOT_CONFIGURED` | 422 | Selected gateway slug exists but admin hasn't filled in keys | Show "Try another payment method" with the working gateways listed | Yes via different gateway |
| `GATEWAY_ERROR` | 502 | Gateway's createOrder threw (network / 5xx from gateway) | Retry dialog + support contact | Yes |
| `PLAN_GONE` | 422 | Plan was deleted between order creation and verify | Contact-support screen | No |

## Adding a new error code

1. Add a row to one of the tables above.
2. Emit it via `ApiResponse::error('YOUR_CODE', $message, $fields, $status)` from a controller, or add a branch in `App\Exceptions\ApiExceptionHandler` if it should fire automatically from an exception.
3. Add a Pest test in `tests/Feature/Api/V1/` asserting the code surfaces under the documented circumstances.
4. Update Flutter's `ApiException` switch if it needs a special UX (default falls back to a generic error view).
5. Re-run `php tests/Tools/openapi-validate.php` to confirm Scribe picks up the new `@response` annotation.

## Flutter side — UX dispatch

Flutter's `ApiException` is the single demarcation point. The widget that owns user-facing error rendering looks roughly like:

```dart
Widget errorFor(ApiException e) {
  return switch (e.code) {
    // Field-level inline errors (forms)
    'VALIDATION_FAILED' => ValidationErrorInline(fields: e.fields ?? {}),

    // Auth
    'UNAUTHENTICATED' => SizedBox.shrink(),  // interceptor already routed to /login
    'OTP_INVALID' => OtpShakeInput(message: e.message),

    // Upgrade-flow CTAs
    'DAILY_LIMIT_REACHED' => DailyLimitDialog(message: e.message),
    'PREMIUM_REQUIRED' => UpgradeDialog(),
    'LOW_MATCH_SCORE' => MatchScoreGateDialog(message: e.message),

    // Onboarding redirects
    'PROFILE_REQUIRED' => RouteTo.profileCreation(),
    'PREFERENCES_REQUIRED' => RouteTo.partnerPreferences(),

    // Payment
    'COUPON_INVALID' => SnackBar(content: Text(e.message)),
    'SIGNATURE_INVALID' => PaymentRetryDialog(),
    'GATEWAY_NOT_CONFIGURED' => GatewayPickerDialog(),
    'GATEWAY_ERROR' => PaymentRetryDialog(),
    'PLAN_GONE' => ContactSupportDialog(),

    // Throttle / rate limit
    'THROTTLED' => ThrottledBanner(message: e.message, retryAfter: e.retryAfter),

    // Generic fallback
    _ => GenericErrorView(message: e.message),
  };
}
```

## Cross-reference

- Server-side emission: every code is emitted somewhere under `app/Http/Controllers/Api/V1/*` or `app/Exceptions/ApiExceptionHandler.php`.
- Pest coverage: every code has at least one `expect($body['error']['code'])->toBe('YOUR_CODE')` assertion under `tests/Feature/Api/V1/*`.
- OpenAPI: every code appears in at least one `@response` docblock annotation, surfaced by `php artisan scribe:generate`.
- Static lint: `php tests/Tools/openapi-validate.php` flags drift between code and spec.
