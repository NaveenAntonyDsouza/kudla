# Step 16 — Bruno Test Collection

## Goal
Every `/api/v1/*` endpoint is exercisable through a committed Bruno
collection. One `bru run` command verifies the whole API is green
end-to-end.

Why Bruno over Postman: it's a flat-file format (`.bru`) that commits
cleanly to git. No cloud sync, no licenses, no binary blobs. Runs in CLI
mode for CI.

## Prerequisites
- [ ] Step 15 complete (all ~82 endpoints live)
- [ ] Bruno installed: https://www.usebruno.com/downloads (or `npm i -g @usebruno/cli`)

## Procedure

### 1. Scaffold directory structure

```bash
cd docs
mkdir -p bruno/kudla-api-v1/{environments,01-auth,02-profiles,03-photos,04-search-discover,05-interests-chat,06-membership,07-notifications,08-engagement,09-settings,10-devices,11-reference}
```

### 2. Create `bruno.json` at collection root

`docs/bruno/kudla-api-v1/bruno.json`:
```json
{
  "version": "1",
  "name": "Kudla API v1",
  "type": "collection",
  "ignore": ["node_modules", ".git"]
}
```

### 3. Create environments

`docs/bruno/kudla-api-v1/environments/local.bru`:
```
vars {
  baseUrl: http://127.0.0.1:8765
  token: ""
}
```

Mirror `staging.bru` (pointing to a staging domain, once provisioned) and
`prod.bru`.

### 4. Write `.bru` file per endpoint

**Template** (saved as e.g. `01-auth/register-step-1.bru`):

```
meta {
  name: Register Step 1
  type: http
  seq: 1
}

post {
  url: {{baseUrl}}/api/v1/auth/register/step-1
  body: json
  auth: none
}

body:json {
  {
    "full_name": "Bruno Test",
    "email": "bruno-{{$timestamp}}@test.local",
    "phone": "98888{{$randomInt(10000, 99999)}}",
    "password": "password",
    "gender": "male",
    "date_of_birth": "1995-04-12"
  }
}

vars:post-response {
  token: res.body.data.token
}

tests {
  test("status is 201", () => {
    expect(res.status).to.equal(201);
  });
  test("envelope success", () => {
    expect(res.body.success).to.equal(true);
  });
  test("returns a token", () => {
    expect(res.body.data.token).to.be.a("string");
    expect(res.body.data.token.length).to.be.above(10);
  });
  test("profile has matri_id", () => {
    expect(res.body.data.profile.matri_id).to.match(/^AM\d+$/);
  });
}
```

### 5. Cover every endpoint

One `.bru` per endpoint. Group by tag:

| Group | Endpoints |
|-------|-----------|
| 01-auth | register (5), OTP (4), login (1), password (2), me, logout |
| 02-profiles | dashboard, me, show, update (9 sections) |
| 03-photos | list, upload, primary, delete, restore, privacy, requests (4) |
| 04-search-discover | partner, keyword, id, saved (3), discover (3), matches (3) |
| 05-interests-chat | inbox, show, send, accept/decline/cancel/star/trash, reply, since |
| 06-membership | plans, me, coupon, order, verify, history, webhook |
| 07-notifications | list, read, read-all, unread-count |
| 08-engagement | shortlist (2), views, block (3), report, ignore (2), id-proof (3), success-stories (2), contact, static-pages |
| 09-settings | 7 endpoints |
| 10-devices | register, revoke |
| 11-reference | list, show, site-settings, health |

~82 `.bru` files total.

### 6. CLI smoke run

Install CLI:
```bash
npm install -g @usebruno/cli
```

Run whole collection:
```bash
bru run docs/bruno/kudla-api-v1 --env local
```

All should pass. Non-zero exit = a drifted endpoint.

### 7. Add to `.gitignore`

Bruno creates `.bruno-cli` temp files — ignore them:
```
# .gitignore
/docs/bruno/**/bruno-lock.json
/docs/bruno/**/.bruno-cli
```

### 8. Commit

```bash
git add docs/bruno/kudla-api-v1 .gitignore
git commit -m "phase-2a wk-04: step-16 Bruno test collection (~82 endpoints)"
```

## Verification

- [ ] Every endpoint in [reference/endpoint-catalogue.md](../../reference/endpoint-catalogue.md) has a matching `.bru`
- [ ] `bru run docs/bruno/kudla-api-v1 --env local` exits 0
- [ ] Each `.bru` asserts envelope shape (`success`, `data` OR `error.code`)
- [ ] Auth-required endpoints use `{{token}}` env var populated by login `.bru`'s `vars:post-response`

## Next step
→ [step-17-contract-snapshot-tests.md](step-17-contract-snapshot-tests.md)
