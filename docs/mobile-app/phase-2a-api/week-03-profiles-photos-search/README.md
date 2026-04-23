# Phase 2a — Week 3: Profiles, Photos, Search

**Goal:** complete read + write endpoints for profiles (own + other + 9 editable sections), photos (upload/manage + requests + privacy), search (partner/keyword/matri-id/saved), discover (hub/category/results), and match scores.

**Design reference:** [`design/04-profile-api.md`](../../design/04-profile-api.md), [`design/05-photo-api.md`](../../design/05-photo-api.md), [`design/06-search-discover-api.md`](../../design/06-search-discover-api.md)

**Prerequisite:** [Week 2 acceptance](../week-02-auth-registration/week-02-acceptance.md) ✅ passed.

---

## Steps

| # | Step | Status |
|---|------|--------|
| 1 | [Profile Resource classes (Card + Full + Dashboard)](step-01-profile-resources.md) | ☐ |
| 2 | [ProfileAccessService — 7-gate privacy checks](step-02-profile-access-service.md) | ☐ |
| 3 | [GET /api/v1/dashboard endpoint](step-03-dashboard-endpoint.md) | ☐ |
| 4 | [GET /api/v1/profile/me endpoint](step-04-profile-me-endpoint.md) | ☐ |
| 5 | [GET /api/v1/profiles/{matriId} with gates + ProfileViewService tracking](step-05-view-other-profile.md) | ☐ |
| 6 | [PUT /api/v1/profile/me/{section} — 9 section update endpoints](step-06-update-profile-section.md) | ☐ |
| 7 | [Photo multi-driver absolute URL contract (PhotoResource)](step-07-photo-resource.md) | ☐ |
| 8 | [photo_access_grants migration + PhotoAccessService](step-08-photo-access-grants.md) | ☐ |
| 9 | [Photo upload + list + set primary + delete + restore](step-09-photo-crud-endpoints.md) | ☐ |
| 10 | [Photo privacy update endpoint](step-10-photo-privacy-endpoint.md) | ☐ |
| 11 | [Photo request lifecycle endpoints](step-11-photo-request-endpoints.md) | ☐ |
| 12 | [GET /api/v1/search (partner filters)](step-12-search-partner-endpoint.md) | ☐ |
| 13 | [Keyword + matri-ID search + saved searches](step-13-keyword-id-saved.md) | ☐ |
| 14 | [Discover hub + category + results endpoints](step-14-discover-endpoints.md) | ☐ |
| 15 | [Match endpoints (my, mutual, score)](step-15-match-endpoints.md) | ☐ |

**End-of-week acceptance:** [week-03-acceptance.md](week-03-acceptance.md)

---

## Deliverables

- **15 new endpoints + ~8 Resource classes**
- **ProfileAccessService** enforcing all 7 privacy gates
- **PhotoAccessService** handling per-viewer photo visibility
- **ProfileViewService::track()** deduped to 24h
- Updated Scribe docs with Profile, Photo, Search, Discover, Match groups

---

## Time budget

~32 hours (~1.5 weeks realistically). This is the biggest week of Phase 2a — don't rush the privacy gates.

| Day | Steps |
|-----|-------|
| Mon–Tue | 1-6: profile layer |
| Wed–Thu | 7-11: photo layer |
| Fri | 12-15: search + discover + matches |
| Weekend buffer | acceptance + fixes |

---

**Start:** [Step 1 — Profile Resources →](step-01-profile-resources.md)
