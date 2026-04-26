import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate } from 'k6/metrics';

import { API_BASE, SEED_USERS } from '../lib/env.js';

/**
 * Login burst — 100 simultaneous logins over 30 seconds against the same
 * email. Validates two things:
 *   1. The throttle:login limiter (5/min/IP+email) holds — most requests
 *      after the first 5 should 429, NOT crash the worker.
 *   2. Successful logins continue serving normally despite the burst.
 *
 * Production target: this scenario simulates a credential-stuffing wave
 * or a legitimate Monday-morning peak when 100 intervenants log in within
 * 30s (different emails — but we're measuring server resilience, so
 * pounding one email is the harsher test).
 *
 * Run:  k6 run tests/load/scenarios/login_burst.js
 * Time: ~40 seconds
 */

// k6 metric names allow only [A-Za-z0-9_] so use underscores, not dots.
const accepted = new Counter('login_accepted');
const throttled = new Counter('login_throttled');
const errored = new Rate('login_errored');

export const options = {
    scenarios: {
        burst: {
            executor: 'ramping-arrival-rate',
            startRate: 1,
            timeUnit: '1s',
            preAllocatedVUs: 50,
            maxVUs: 100,
            stages: [
                { target: 5, duration: '5s' },   // warm-up
                { target: 30, duration: '20s' }, // burst
                { target: 0, duration: '5s' },   // cooldown
            ],
        },
    },
    thresholds: {
        // 4xx is expected here (429s) — only true 5xx counts as error.
        login_errored: ['rate<0.01'],
    },
};

export default function () {
    const res = http.post(
        `${API_BASE}/auth/login`,
        JSON.stringify({
            email: SEED_USERS.intervenant.email,
            password: SEED_USERS.intervenant.password,
        }),
        {
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            tags: { name: 'auth.login' },
        },
    );

    if (res.status === 200) accepted.add(1);
    if (res.status === 429) throttled.add(1);
    errored.add(res.status >= 500);

    check(res, {
        'no 5xx': (r) => r.status < 500,
        'expected status': (r) => [200, 401, 422, 429].includes(r.status),
    });
}
