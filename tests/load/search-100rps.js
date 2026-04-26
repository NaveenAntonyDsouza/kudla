/*
 * k6 load test for the hot endpoints.
 *
 * Acceptance gate: p95 < 400ms at 100 concurrent VUs for 5 min, with
 * zero failed requests. Mirrors the criterion in
 * docs/mobile-app/phase-2a-api/week-04-interests-payment-push/week-04-acceptance.md.
 *
 * Usage:
 *   BEARER_TOKEN=<token> k6 run tests/load/search-100rps.js
 *
 * The token must belong to a user with a completed profile + saved
 * partner preferences (otherwise /search/partner returns an empty
 * paginator and skews timings low). Use `php artisan tinker` to seed
 * one once per environment.
 */

import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    scenarios: {
        steady: {
            executor: 'constant-vus',
            vus: 100,
            duration: '5m',
        },
    },
    thresholds: {
        // Acceptance gate
        http_req_duration: ['p(95)<400'],
        http_req_failed: ['rate<0.01'],
        // Per-endpoint p95s — useful for spotting which one to fix first.
        'http_req_duration{endpoint:search}': ['p(95)<400'],
        'http_req_duration{endpoint:dashboard}': ['p(95)<400'],
        'http_req_duration{endpoint:matches}': ['p(95)<400'],
        'http_req_duration{endpoint:profile_show}': ['p(95)<400'],
        'http_req_duration{endpoint:discover}': ['p(95)<400'],
    },
};

const BASE = __ENV.BASE_URL || 'http://127.0.0.1:8000';
const TOKEN = __ENV.BEARER_TOKEN;
const TARGET_MATRI_ID = __ENV.TARGET_MATRI_ID || 'AM100002';

if (!TOKEN) {
    throw new Error('BEARER_TOKEN env var is required. See acceptance-runbook.md §5.');
}

const headers = {
    Authorization: `Bearer ${TOKEN}`,
    Accept: 'application/json',
};

const ENDPOINTS = [
    // weight, endpoint tag, URL
    [4, 'search', '/api/v1/search/partner?per_page=20'],
    [3, 'dashboard', '/api/v1/dashboard'],
    [2, 'matches', '/api/v1/matches/my?per_page=20'],
    [2, 'profile_show', `/api/v1/profiles/${TARGET_MATRI_ID}`],
    [1, 'discover', '/api/v1/discover'],
];

// Build a weighted picker — search hit 4× more often than discover.
const pool = [];
for (const [weight, tag, url] of ENDPOINTS) {
    for (let i = 0; i < weight; i++) {
        pool.push({ tag, url });
    }
}

export default function () {
    const pick = pool[Math.floor(Math.random() * pool.length)];
    const res = http.get(BASE + pick.url, {
        headers,
        tags: { endpoint: pick.tag },
    });

    check(res, {
        'status 2xx': (r) => r.status >= 200 && r.status < 300,
        'envelope success=true': (r) => {
            try {
                return JSON.parse(r.body).success === true;
            } catch (_) {
                return false;
            }
        },
    });

    // Tiny think-time so we don't hammer all 100 VUs in lockstep
    sleep(Math.random() * 0.5);
}
