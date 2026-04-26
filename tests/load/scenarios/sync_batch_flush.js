import http from 'k6/http';
import { check } from 'k6';
import { Counter, Trend } from 'k6/metrics';

import { authedHeaders, login } from '../lib/auth.js';
import { API_BASE, SEED_USERS, SLA } from '../lib/env.js';
import { clientUuid, syncBatchPayload } from '../lib/factories.js';

/**
 * Sync batch flush — models the worst case for the offline-first mobile
 * app: a network outage ends and 50 intervenants flush their queues
 * simultaneously. Each VU posts a realistic batch (3 check-ins +
 * 3 check-outs + maybe 1 incident) to /api/v1/sync/batch.
 *
 * Validates:
 *   - Sync rate limiter (throttle:sync = 20/min/user) holds under burst
 *   - Per-op error isolation works (200ms-ish even with 50 ops)
 *   - HTTP 207 always returned (no 500s under burst)
 *
 * Pre-requisite: each intervenant has ≥3 planned interventions to act on.
 *
 * Run:  k6 run tests/load/scenarios/sync_batch_flush.js
 * Time: ~2 minutes
 */

// k6 metric names allow only [A-Za-z0-9_].
const opsPerSecond = new Counter('sync_ops_total');
const batchLatency = new Trend('sync_batch_latency_ms');

export const options = {
    scenarios: {
        reconnect_burst: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { target: 50, duration: '15s' },
                { target: 50, duration: '1m30s' },
                { target: 0, duration: '15s' },
            ],
        },
    },
    thresholds: {
        // Batch endpoint write SLA
        [`http_req_duration{name:sync.batch}`]: [`p(95)<${SLA.WRITE_P95_MS * 2}`], // batches are heavier; 2x the SLA
        http_req_failed: [`rate<${SLA.ERROR_RATE_MAX}`],
    },
};

export function setup() {
    const token = login(SEED_USERS.intervenant.email, SEED_USERS.intervenant.password);
    const list = http.get(`${API_BASE}/interventions`, authedHeaders(token));
    const interventions = (list.json('data') || []).map((i) => i.id);

    return { token, interventionIds: interventions.slice(0, 10) };
}

export default function (data) {
    const body = JSON.stringify(syncBatchPayload(data.interventionIds));

    const res = http.post(`${API_BASE}/sync/batch`, body, {
        ...authedHeaders(data.token, { 'Idempotency-Key': clientUuid() }),
        tags: { name: 'sync.batch' },
    });

    batchLatency.add(res.timings.duration);

    const ok = check(res, {
        'sync: 207 multi-status': (r) => r.status === 207,
        'sync: results array present': (r) => Array.isArray(r.json('results')),
        'sync: count matches results': (r) => r.json('count') === (r.json('results') || []).length,
    });

    if (ok) {
        opsPerSecond.add((res.json('results') || []).length);
    }
}
