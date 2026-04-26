import http from 'k6/http';
import { check } from 'k6';

import { authedHeaders, login } from '../lib/auth.js';
import { API_BASE, SEED_USERS, SLA } from '../lib/env.js';

/**
 * Dashboard p95 — the headline SLA from IMPLEMENTATION_PLAN.txt:362-364:
 *   "verify p95 < 500ms on dashboard"
 *
 * Models: 50 concurrent coordinateurs hitting their dashboard view for
 * 5 minutes (typical morning standup window). Validates the cache layer
 * (DashboardStatsService is Redis-tagged, 5-min TTL) and that the
 * underlying queries don't degrade under sustained read pressure.
 *
 * Note: the "dashboard" is the Inertia /dashboard page on web, but for
 * the load test we hit the data endpoints the dashboard reads from:
 *   - GET /api/v1/auth/me                (user context)
 *   - GET /api/v1/interventions          (today's tour)
 *   - GET /api/v1/incidents              (recent incidents feed)
 *
 * Run:  k6 run tests/load/scenarios/dashboard_p95.js
 * Time: ~5 minutes 30 seconds
 */
export const options = {
    scenarios: {
        sustained: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { target: 50, duration: '30s' },  // ramp up
                { target: 50, duration: '4m' },   // hold
                { target: 0, duration: '30s' },   // ramp down
            ],
        },
    },
    thresholds: {
        // Plan-mandated SLA per IMPLEMENTATION_PLAN.txt:362.
        // Tag the requests via tags:{name:...} so each endpoint gets its
        // own p95 series; SLA fails the run if any breaches.
        'http_req_duration{name:dashboard_interventions}': [`p(95)<${SLA.READ_P95_MS}`],
        'http_req_duration{name:dashboard_incidents}': [`p(95)<${SLA.READ_P95_MS}`],
        'http_req_duration{name:dashboard_me}': [`p(95)<${SLA.READ_P95_MS}`],
        // Overall error rate must stay below 1%.
        http_req_failed: [`rate<${SLA.ERROR_RATE_MAX}`],
    },
};

export function setup() {
    return { token: login(SEED_USERS.coordinateur.email, SEED_USERS.coordinateur.password) };
}

export default function (data) {
    const r1 = http.get(`${API_BASE}/interventions`, {
        ...authedHeaders(data.token),
        tags: { name: 'dashboard_interventions' },
    });
    check(r1, { 'interventions: 200': (r) => r.status === 200 });

    const r2 = http.get(`${API_BASE}/incidents`, {
        ...authedHeaders(data.token),
        tags: { name: 'dashboard_incidents' },
    });
    check(r2, { 'incidents: 200': (r) => r.status === 200 });

    const r3 = http.get(`${API_BASE}/auth/me`, {
        ...authedHeaders(data.token),
        tags: { name: 'dashboard_me' },
    });
    check(r3, { 'me: 200': (r) => r.status === 200 });
}
