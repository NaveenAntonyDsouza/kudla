# Week 3 Acceptance Checkpoint

Verify everything below before Week 4.

---

## Endpoint checklist (15 new)

- [ ] `GET /api/v1/dashboard` — all 7 sections present
- [ ] `GET /api/v1/profile/me` — 9 sections
- [ ] `GET /api/v1/profiles/{matriId}` — with gates applied
- [ ] `PUT /api/v1/profile/me/{section}` — all 9 sections updatable
- [ ] `GET /api/v1/photos` — grouped list
- [ ] `POST /api/v1/photos` — upload multipart
- [ ] `POST /api/v1/photos/{id}/primary`
- [ ] `DELETE /api/v1/photos/{id}` — archive
- [ ] `POST /api/v1/photos/{id}/restore`
- [ ] `POST /api/v1/photos/privacy`
- [ ] `GET /api/v1/photo-requests`
- [ ] `POST /api/v1/profiles/{matriId}/photo-request`
- [ ] `POST /api/v1/photo-requests/{id}/approve`
- [ ] `POST /api/v1/photo-requests/{id}/ignore`
- [ ] `GET /api/v1/search` — partner filters
- [ ] `GET /api/v1/search/keyword` — fuzzy search
- [ ] `GET /api/v1/search/id/{matriId}`
- [ ] `GET /api/v1/search/saved`
- [ ] `POST /api/v1/search/saved`
- [ ] `DELETE /api/v1/search/saved/{id}`
- [ ] `GET /api/v1/discover`
- [ ] `GET /api/v1/discover/{category}`
- [ ] `GET /api/v1/discover/{category}/{slug}`
- [ ] `GET /api/v1/matches/my`
- [ ] `GET /api/v1/matches/mutual`
- [ ] `GET /api/v1/matches/score/{matriId}`

---

## Gate/privacy checklist

- [ ] Same-gender profile view returns 403 GENDER_MISMATCH
- [ ] Blocked profile view returns 404
- [ ] Hidden profile view returns 404 (unless existing interest)
- [ ] Suspended profile view returns 404
- [ ] Non-premium viewer sees `contact: null` on other profiles
- [ ] Non-premium viewer sees `photos[].is_blurred = true` when target has `blur_non_premium`
- [ ] After photo-request approved, photos un-blur for grantee

## Service layer

- [ ] `ProfileAccessService` returns correct REASON_* for each gate
- [ ] `PhotoAccessService::grant()` and `hasAccess()` work
- [ ] `ProfileViewService::track()` dedupes at 24h

## Tests

```bash
./vendor/bin/pest --parallel
```

- [ ] All tests green (add up to ~60 test functions across Weeks 1-3)
- [ ] No flaky tests

## Scribe regenerate

```bash
php artisan scribe:generate
```

- [ ] Scribe now shows Profile, Photos, Photo Requests, Search, Saved Searches, Discover, Matches groups
- [ ] OpenAPI spec at `/docs.openapi.yaml` validates

---

## Go/No-Go for Week 4

All ✅ → [phase-2a-api/week-04-interests-payment-push/README.md](../week-04-interests-payment-push/README.md)

**Week 3 complete ✅**
