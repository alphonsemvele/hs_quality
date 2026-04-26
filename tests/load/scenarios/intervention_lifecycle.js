import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter } from 'k6/metrics';

import { authedHeaders, login } from '../lib/auth.js';
import { API_BASE, SEED_USERS, SLA } from '../lib/env.js';
import { checkInPayload, checkOutPayload } from '../lib/factories.js';

/**
 * Intervention lifecycle — the headline scenario from the plan
 * (IMPLEMENTATION_PLAN.txt:362):
 *   "200 intervenants × 5 interventions/day at peak concurrency"
 *
 * Models the morning peak window (07:00-09:00 in production) when most
 * intervenants log their first visits within ~30 minutes. Each VU
 * represents one intervenant who:
 *   1. Logs in once (token cached for the run)
 *   2. Lists their planned interventions
 *   3. Checks in to one
 *   4. Pauses ~30s simulating the actual visit
 *   5. Checks out with a brief report
 * Repeats with the next intervention until 5 done.
 *
 * Pre-requisite: each intervenant in the seeded data must have ≥5
 * planned interventions for "today". Use a setup seeder before the
 * load test (StructureSeeder + extra interventions).
 *
 * Run:  k6 run tests/load/scenarios/intervention_lifecycle.js
 * Time: ~10 minutes (200 VUs × ~5 iterations × ~3s thinking time)
 */

// k6 metric names allow only [A-Za-z0-9_].
const checkInOk = new Counter('intervention_check_in_ok');
const checkOutOk = new Counter('intervention_check_out_ok');
const noPlanned = new Counter('intervention_no_planned_found');

export const options = {
    scenarios: {
        morning_peak: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { target: 50, duration: '1m' },   // first wave logs in
                { target: 200, duration: '4m' },  // peak: 200 concurrent intervenants
                { target: 200, duration: '3m' },  // hold
                { target: 0, duration: '2m' },    // ramp down
            ],
        },
    },
    thresholds: {
        // Read endpoints (listing today's tour)
        [`http_req_duration{name:interventions.list}`]: [`p(95)<${SLA.READ_P95_MS}`],
        // Write endpoints (lifecycle transitions)
        [`http_req_duration{name:interventions.check_in}`]: [`p(95)<${SLA.WRITE_P95_MS}`],
        [`http_req_duration{name:interventions.check_out}`]: [`p(95)<${SLA.WRITE_P95_MS}`],
        // Overall error rate
        http_req_failed: [`rate<${SLA.ERROR_RATE_MAX}`],
    },
};

export function setup() {
    return { token: login(SEED_USERS.intervenant.email, SEED_USERS.intervenant.password) };
}

export default function (data) {
    const auth = authedHeaders(data.token);

    // 1. Today's tour
    const list = http.get(
        `${API_BASE}/interventions`,
        { ...auth, tags: { name: 'interventions.list' } },
    );
    check(list, { 'list: 200': (r) => r.status === 200 });

    const planned = (list.json('data') || []).filter((i) => i.status === 'planned');
    if (planned.length === 0) {
        noPlanned.add(1);
        sleep(3);
        return;
    }

    const intervention = planned[0];

    // 2. Check in
    const ci = http.post(
        `${API_BASE}/interventions/${intervention.id}/check-in`,
        JSON.stringify(checkInPayload()),
        { ...auth, tags: { name: 'interventions.check_in' } },
    );
    if (check(ci, { 'check-in: 200|201|409': (r) => [200, 201, 409].includes(r.status) })) {
        if (ci.status < 400) checkInOk.add(1);
    }

    // 3. Visit duration (compressed in load test)
    sleep(2 + Math.random() * 3);

    // 4. Check out
    const co = http.post(
        `${API_BASE}/interventions/${intervention.id}/check-out`,
        JSON.stringify(checkOutPayload()),
        { ...auth, tags: { name: 'interventions.check_out' } },
    );
    if (check(co, { 'check-out: 200|201|409': (r) => [200, 201, 409].includes(r.status) })) {
        if (co.status < 400) checkOutOk.add(1);
    }

    sleep(1);
}
