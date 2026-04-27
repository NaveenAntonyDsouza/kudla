# API Endpoint Catalogue

All ~95 endpoints under `/api/v1/*`. Sorted by group. Authoritative — kept in sync with `routes/api.php` via `php tests/Tools/route-audit.php`.

Legend:
- 🔓 Public (no auth)
- 🔐 Sanctum bearer required
- ⏱ Rate limited

---

## Authentication (🔓 where marked unauthenticated)

| Method | Path | Auth | Rate | Covered in |
|--------|------|------|------|-----------|
| POST | `/auth/register/step-1` | 🔓 | 5/min | [wk-02/step-06](../phase-2a-api/week-02-auth-registration/step-06-register-step-1-endpoint.md) |
| POST | `/auth/register/step-2` | 🔐 | | [wk-02/step-07](../phase-2a-api/week-02-auth-registration/step-07-register-steps-2-5.md) |
| POST | `/auth/register/step-3` | 🔐 | | [wk-02/step-07](../phase-2a-api/week-02-auth-registration/step-07-register-steps-2-5.md) |
| POST | `/auth/register/step-4` | 🔐 | | [wk-02/step-07](../phase-2a-api/week-02-auth-registration/step-07-register-steps-2-5.md) |
| POST | `/auth/register/step-5` | 🔐 | | [wk-02/step-07](../phase-2a-api/week-02-auth-registration/step-07-register-steps-2-5.md) |
| POST | `/auth/otp/phone/send` | 🔓 | 5/min | [wk-02/step-08](../phase-2a-api/week-02-auth-registration/step-08-phone-otp-endpoints.md) |
| POST | `/auth/otp/phone/verify` | 🔓 | 10/min | [wk-02/step-08](../phase-2a-api/week-02-auth-registration/step-08-phone-otp-endpoints.md) |
| POST | `/auth/otp/email/send` | 🔓 | 5/min | [wk-02/step-09](../phase-2a-api/week-02-auth-registration/step-09-email-otp-endpoints.md) |
| POST | `/auth/otp/email/verify` | 🔓 | 10/min | [wk-02/step-09](../phase-2a-api/week-02-auth-registration/step-09-email-otp-endpoints.md) |
| POST | `/auth/login/password` | 🔓 | 10/min | [wk-02/step-10](../phase-2a-api/week-02-auth-registration/step-10-login-password.md) |
| POST | `/auth/password/forgot` | 🔓 | 5/min | [wk-02/step-13](../phase-2a-api/week-02-auth-registration/step-13-forgot-reset-password.md) |
| POST | `/auth/password/reset` | 🔓 | 5/min | [wk-02/step-13](../phase-2a-api/week-02-auth-registration/step-13-forgot-reset-password.md) |
| GET | `/auth/me` | 🔐 | | [wk-02/step-14](../phase-2a-api/week-02-auth-registration/step-14-me-logout.md) |
| GET | `/auth/ping` | 🔐 | | [wk-01/step-02](../phase-2a-api/week-01-foundations/step-02-api-routes-skeleton.md) |
| POST | `/auth/logout` | 🔐 | | [wk-02/step-14](../phase-2a-api/week-02-auth-registration/step-14-me-logout.md) |

## Devices

| POST | `/devices` | 🔐 | | [wk-02/step-15](../phase-2a-api/week-02-auth-registration/step-15-device-registration.md) |
| DELETE | `/devices/{device}` | 🔐 | | [wk-02/step-15](../phase-2a-api/week-02-auth-registration/step-15-device-registration.md) |

## Onboarding

| POST | `/onboarding/step-1` | 🔐 | | [wk-04/step-14](../phase-2a-api/week-04-interests-payment-push/step-14-onboarding-endpoints.md) |
| POST | `/onboarding/step-2` | 🔐 | | wk-04/step-14 |
| POST | `/onboarding/partner-preferences` | 🔐 | | wk-04/step-14 |
| POST | `/onboarding/lifestyle` | 🔐 | | wk-04/step-14 |
| POST | `/onboarding/finish` | 🔐 | | wk-04/step-14 |

## Configuration (🔓)

| GET | `/site/settings` | 🔓 | | [wk-01/step-06](../phase-2a-api/week-01-foundations/step-06-site-settings-endpoint.md) |
| GET | `/reference` | 🔓 | | [wk-01/step-07](../phase-2a-api/week-01-foundations/step-07-reference-data-endpoints.md) |
| GET | `/reference/{list}` | 🔓 | | [wk-01/step-07](../phase-2a-api/week-01-foundations/step-07-reference-data-endpoints.md) |
| GET | `/static-pages/{slug}` | 🔓 | | [wk-04/step-13](../phase-2a-api/week-04-interests-payment-push/step-13-engagement-public.md) |

## Dashboard + Profile

| GET | `/dashboard` | 🔐 | | [wk-03/step-03](../phase-2a-api/week-03-profiles-photos-search/step-03-dashboard-endpoint.md) |
| GET | `/profile/me` | 🔐 | | [wk-03/step-04](../phase-2a-api/week-03-profiles-photos-search/step-04-profile-me-endpoint.md) |
| GET | `/profiles/{matriId}` | 🔐 | | [wk-03/step-05](../phase-2a-api/week-03-profiles-photos-search/step-05-view-other-profile.md) |
| PUT | `/profile/me/{section}` | 🔐 | | [wk-03/step-06](../phase-2a-api/week-03-profiles-photos-search/step-06-update-profile-section.md) |

## Photos

| GET | `/photos` | 🔐 | | [wk-03/step-09](../phase-2a-api/week-03-profiles-photos-search/step-09-photo-crud-endpoints.md) |
| POST | `/photos` | 🔐 | 20/hr | wk-03/step-09 |
| POST | `/photos/{photo}/primary` | 🔐 | | wk-03/step-09 |
| DELETE | `/photos/{photo}` | 🔐 | | wk-03/step-09 |
| DELETE | `/photos/{photo}/permanent` | 🔐 | | wk-03/step-09 |
| POST | `/photos/{photo}/restore` | 🔐 | | wk-03/step-09 |
| POST | `/photos/privacy` | 🔐 | | [wk-03/step-10](../phase-2a-api/week-03-profiles-photos-search/step-10-photo-privacy-endpoint.md) |
| GET | `/photo-requests` | 🔐 | | [wk-03/step-11](../phase-2a-api/week-03-profiles-photos-search/step-11-photo-request-endpoints.md) |
| POST | `/profiles/{matriId}/photo-request` | 🔐 | | wk-03/step-11 |
| POST | `/photo-requests/{id}/approve` | 🔐 | | wk-03/step-11 |
| POST | `/photo-requests/{id}/ignore` | 🔐 | | wk-03/step-11 |

## Search, Discover, Matches

| GET | `/search/partner` | 🔐 | | [wk-03/step-12](../phase-2a-api/week-03-profiles-photos-search/step-12-search-partner-endpoint.md) |
| GET | `/search/keyword` | 🔐 | | [wk-03/step-13](../phase-2a-api/week-03-profiles-photos-search/step-13-keyword-id-saved.md) |
| GET | `/search/id/{matriId}` | 🔐 | | wk-03/step-13 |
| GET | `/search/saved` | 🔐 | | wk-03/step-13 |
| POST | `/search/saved` | 🔐 | | wk-03/step-13 |
| DELETE | `/search/saved/{id}` | 🔐 | | wk-03/step-13 |
| GET | `/discover` | 🔓 | | [wk-03/step-14](../phase-2a-api/week-03-profiles-photos-search/step-14-discover-endpoints.md) |
| GET | `/discover/{category}` | 🔓 | | wk-03/step-14 |
| GET | `/discover/{category}/{slug}` | 🔓 | | wk-03/step-14 |
| GET | `/matches/my` | 🔐 | | [wk-03/step-15](../phase-2a-api/week-03-profiles-photos-search/step-15-match-endpoints.md) |
| GET | `/matches/mutual` | 🔐 | | wk-03/step-15 |
| GET | `/matches/score/{matriId}` | 🔐 | 30/hr | wk-03/step-15 |

## Interests & Chat

| GET | `/interests` | 🔐 | | [wk-04/step-01](../phase-2a-api/week-04-interests-payment-push/step-01-interest-endpoints.md) |
| GET | `/interests/{interest}` | 🔐 | | wk-04/step-01 |
| POST | `/profiles/{matriId}/interest` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/accept` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/decline` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/cancel` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/star` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/trash` | 🔐 | | wk-04/step-01 |
| POST | `/interests/{interest}/messages` | 🔐 | 30/hr | wk-04/step-01 |
| GET | `/interests/{interest}/messages` | 🔐 | | [wk-04/step-02](../phase-2a-api/week-04-interests-payment-push/step-02-chat-polling.md) |

## Membership + Payment

| GET | `/membership/plans` | 🔓 | | [wk-04/step-03](../phase-2a-api/week-04-interests-payment-push/step-03-membership-plans-coupon.md) |
| GET | `/membership/me` | 🔐 | | wk-04/step-03 |
| POST | `/membership/coupon/validate` | 🔐 | | wk-04/step-03 |
| POST | `/payment/{gateway}/order` | 🔐 | | [wk-04/step-04](../phase-2a-api/week-04-interests-payment-push/step-04-razorpay-order-verify.md) |
| POST | `/payment/{gateway}/verify` | 🔐 | | wk-04/step-04 |
| POST | `/webhooks/{gateway}` | 🔓 | | [wk-04/step-05](../phase-2a-api/week-04-interests-payment-push/step-05-razorpay-webhook.md) |

`{gateway}` ∈ `razorpay | stripe | paypal | paytm | phonepe`. Multi-gateway architecture — each slug resolves through `PaymentGatewayManager`. The matching `/webhooks/{gateway}` endpoint stays public and is signature-verified per-gateway in the gateway service. Membership purchase history is exposed via `GET /membership/me` (current snapshot); full purchase history is admin-only and not exposed on the API surface.

## Notifications

| GET | `/notifications` | 🔐 | | [wk-04/step-08](../phase-2a-api/week-04-interests-payment-push/step-08-notification-endpoints.md) |
| POST | `/notifications/{id}/read` | 🔐 | | wk-04/step-08 |
| POST | `/notifications/read-all` | 🔐 | | wk-04/step-08 |
| GET | `/notifications/unread-count` | 🔐 | | wk-04/step-08 |

## Engagement (shortlist / views / block / report / ignore / id-proof / success-stories / contact)

| GET | `/shortlist` | 🔐 | | [wk-04/step-09](../phase-2a-api/week-04-interests-payment-push/step-09-shortlist-views.md) |
| POST | `/profiles/{matriId}/shortlist` | 🔐 | | wk-04/step-09 |
| GET | `/views` | 🔐 | | wk-04/step-09 |
| GET | `/blocked` | 🔐 | | [wk-04/step-10](../phase-2a-api/week-04-interests-payment-push/step-10-block-report-ignore.md) |
| POST | `/profiles/{matriId}/block` | 🔐 | | wk-04/step-10 |
| POST | `/profiles/{matriId}/unblock` | 🔐 | | wk-04/step-10 |
| POST | `/profiles/{matriId}/report` | 🔐 | | wk-04/step-10 |
| GET | `/ignored` | 🔐 | | wk-04/step-10 |
| POST | `/profiles/{matriId}/ignore-toggle` | 🔐 | | wk-04/step-10 |
| GET | `/id-proof` | 🔐 | | [wk-04/step-11](../phase-2a-api/week-04-interests-payment-push/step-11-id-proof.md) |
| POST | `/id-proof` | 🔐 | | wk-04/step-11 |
| DELETE | `/id-proof/{idProof}` | 🔐 | | wk-04/step-11 |
| GET | `/success-stories` | 🔓 | | [wk-04/step-13](../phase-2a-api/week-04-interests-payment-push/step-13-engagement-public.md) |
| POST | `/success-stories` | 🔐 | | wk-04/step-13 |
| POST | `/contact` | 🔓 | 5/hr | wk-04/step-13 |

## Settings

| GET | `/settings` | 🔐 | | [wk-04/step-12](../phase-2a-api/week-04-interests-payment-push/step-12-settings.md) |
| PUT | `/settings/visibility` | 🔐 | | wk-04/step-12 |
| PUT | `/settings/alerts` | 🔐 | | wk-04/step-12 |
| PUT | `/settings/password` | 🔐 | | wk-04/step-12 |
| POST | `/settings/hide` | 🔐 | | wk-04/step-12 |
| POST | `/settings/unhide` | 🔐 | | wk-04/step-12 |
| POST | `/settings/delete` | 🔐 | | wk-04/step-12 |

## System

| GET | `/health` | 🔓 | | [wk-01/step-02](../phase-2a-api/week-01-foundations/step-02-api-routes-skeleton.md) |

---

**Total: 96 routes** across 89 path templates (counted by `php artisan route:list --path=api/v1`).

All covered by Pest tests under `tests/Feature/Api/V1/` (652 tests, 2039 assertions). All in Bruno collection at `docs/bruno/kudla-api-v1/`. All documented via Scribe at `/docs` (94 OpenAPI operations).

To verify the catalogue stays in sync with the codebase:

```
php tests/Tools/route-audit.php
```

Reports any registered-but-undocumented or documented-but-unregistered routes.
