import http from 'k6/http';
import { check } from 'k6';

import { authedHeaders, login } from '../lib/auth.js';
import { API_BASE, SEED_USERS } from '../lib/env.js';

/**
 * Smoke test — 1 VU, 1 iteration. Walks the full mobile path: login →
 * fetch interventions → fetch beneficiaries. If this fails, no other
 * scenario is meaningful.
 *
 * Correctness-only: NO latency threshold. The first request against
 * `php artisan serve` (single-threaded dev server) routinely takes
 * 1-2 seconds and is not representative of production. Latency SLAs
 * are measured in dashboard_p95.js + intervention_lifecycle.js.
 *
 * Run:    k6 run tests/load/scenarios/smoke.js
 * Time:   ~5 seconds
 */
export const options = {
    vus: 1,
    iterations: 1,
    thresholds: {
        http_req_failed: ['rate==0'],
    },
};

export default function () {
    const token = login(SEED_USERS.intervenant.email, SEED_USERS.intervenant.password);

    const me = http.get(`${API_BASE}/auth/me`, authedHeaders(token, { Accept: 'application/json' }));
    check(me, { 'me: 200': (r) => r.status === 200 });

    const interventions = http.get(`${API_BASE}/interventions`, authedHeaders(token));
    check(interventions, {
        'interventions: 200': (r) => r.status === 200,
        'interventions: has data': (r) => Array.isArray(r.json('data')),
    });

    const beneficiaries = http.get(`${API_BASE}/beneficiaries`, authedHeaders(token));
    check(beneficiaries, { 'beneficiaries: 200': (r) => r.status === 200 });
}
