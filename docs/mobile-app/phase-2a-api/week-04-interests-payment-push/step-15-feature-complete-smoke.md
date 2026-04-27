# Step 15 — Feature-Complete Smoke Test + Scribe Regeneration

## Goal
All Week 4 endpoints live + curl-verified. Regenerate Scribe with the
full 80+ endpoint surface. This is the "content-complete" milestone —
steps 16–18 then operationalize the UI-safe bar across the whole API.

## Prerequisites
- [ ] Steps 1–14 of Week 4 all committed

## Procedure

### 1. Regenerate Scribe

```bash
php artisan scribe:generate
```

Verify:
- Every route under `/api/v1/*` appears in `/docs`
- `public/docs.openapi.yaml` validates via editor.swagger.io
- `public/docs.postman.json` imports into Postman cleanly

### 2. End-to-end smoke flow

Run a single curl sequence exercising every major feature area added in
Week 4. This is the "does it all actually work together?" proof.

```bash
UNIQ="e2e-$(date +%s)"
EMAIL="${UNIQ}@test.local"
PHONE="9025$(printf "%06d" $((RANDOM % 1000000)))"

TOKEN=$(curl -s -X POST http://127.0.0.1:8765/api/v1/auth/register/step-1 \
    -H "Content-Type: application/json" \
    -d "{\"full_name\":\"E2E Test\",\"email\":\"${EMAIL}\",\"phone\":\"${PHONE}\",\"password\":\"pw123\",\"gender\":\"male\",\"date_of_birth\":\"1995-04-12\"}" \
    | jq -r '.data.token')

# Interest endpoints
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8765/api/v1/interests?tab=received

# Membership
curl -s http://127.0.0.1:8765/api/v1/membership/plans
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8765/api/v1/membership/me

# Notifications
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8765/api/v1/notifications

# Settings
curl -s -H "Authorization: Bearer $TOKEN" http://127.0.0.1:8765/api/v1/settings

# Cleanup …
```

Expand to cover all 15 Week 4 endpoints + interact with Week 3 endpoints
where flows cross (e.g., sending an interest requires viewing a profile).

### 3. Update NEXT_SESSION_PLAN.md

Mark Phase 2a content-complete. Flag that steps 16–18 still pending.

### 4. Commit

```bash
git add public/docs public/docs.openapi.yaml public/docs.postman.json \
        docs/NEXT_SESSION_PLAN.md
git commit -m "phase-2a wk-04: step-15 feature-complete smoke + Scribe regen"
```

## Verification

- [ ] `GET /docs` renders with all ~82 endpoints
- [ ] OpenAPI spec validates
- [ ] Postman collection imports
- [ ] E2E smoke flow all-green
- [ ] NEXT_SESSION_PLAN updated

## Next step
→ [step-16-bruno-collection.md](step-16-bruno-collection.md) — operationalize UI-safe bar
