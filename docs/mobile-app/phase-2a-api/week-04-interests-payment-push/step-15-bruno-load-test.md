# Step 15 — Bruno Test Collection + Load Test

## Goal
- Build a Bruno API test collection covering every endpoint (commit to `docs/postman/` or `bruno/`)
- Run a k6 or ab load test on hot endpoints

## Procedure

### 1. Bruno setup

```bash
# Install Bruno: https://www.usebruno.com/downloads
# Create a new collection at: docs/bruno/kudla-api-v1/
```

Structure:
```
docs/bruno/kudla-api-v1/
├── bruno.json
├── environments/
│   ├── local.bru
│   ├── staging.bru
│   └── prod.bru
├── 01-auth/
│   ├── register-step-1.bru
│   ├── register-step-2.bru
│   ├── ... (all register steps)
│   ├── otp-phone-send.bru
│   ├── otp-phone-verify.bru
│   ├── login-password.bru
│   ├── forgot-password.bru
│   ├── reset-password.bru
│   ├── me.bru
│   ├── logout.bru
│   └── devices-register.bru
├── 02-profile/
│   └── ...
├── 03-photos/
├── 04-search-discover/
├── 05-interests-chat/
├── 06-membership/
├── 07-notifications/
├── 08-engagement/
└── 09-settings/
```

Each `.bru` file contains a request + tests. Example:

```
// 01-auth/register-step-1.bru
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
    "email": "bruno@example.com",
    "phone": "9999900099",
    "password": "password",
    "password_confirmation": "password",
    "gender": "Male",
    "date_of_birth": "1995-04-12"
  }
}
vars:post-response {
  auth_token: res.body.data.token
}
tests {
  expect(res.status).to.equal(201)
  expect(res.body.success).to.equal(true)
  expect(res.body.data.token).to.be.a('string')
}
```

### 2. Run full collection

```bash
# Via Bruno CLI
bru run docs/bruno/kudla-api-v1 --env local
```

All requests should pass.

### 3. Load test

Install k6: https://k6.io/docs/get-started/installation/

Create `load-test.js`:

```javascript
import http from 'k6/http';
import { sleep, check } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<400'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE = 'http://localhost:8000/api/v1';

export default function () {
  const res = http.get(`${BASE}/site/settings`);
  check(res, { 'status 200': (r) => r.status === 200 });
  sleep(1);
}
```

Run:
```bash
k6 run load-test.js
```

Target: p95 < 400ms at 100 concurrent users.

### 4. Commit

```bash
git add docs/bruno/ load-test.js
git commit -m "phase-2a wk-04: step-15 Bruno test collection + k6 load test"
```

## Verification

- [ ] Bruno collection covers all 80 endpoints
- [ ] `bru run` exits 0 locally
- [ ] k6 load test shows p95 < 400ms on `/site/settings` at 100 concurrent users
- [ ] k6 on `/search?religions=Hindu` (warm cache) shows < 600ms p95

## Next step
→ [week-04-acceptance.md](week-04-acceptance.md)
