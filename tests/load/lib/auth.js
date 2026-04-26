import http from 'k6/http';
import { check, fail } from 'k6';

import { API_BASE } from './env.js';

/**
 * Login against POST /api/v1/auth/login and return a Sanctum bearer token.
 *
 * Use ONCE per VU in setup() (or in init context for static logins) and
 * reuse the token across iterations — repeated logins distort latency
 * metrics and would trip the throttle:login limiter (5/min/IP+email).
 */
export function login(email, password) {
    const res = http.post(
        `${API_BASE}/auth/login`,
        JSON.stringify({ email, password }),
        { headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, tags: { name: 'auth.login' } },
    );

    const ok = check(res, {
        'login: 200': (r) => r.status === 200,
        'login: token returned': (r) => r.json('token') !== undefined,
    });

    if (!ok) {
        fail(`login failed for ${email}: HTTP ${res.status} body=${res.body}`);
    }

    return res.json('token');
}

/**
 * Wrap http.* helpers with the bearer header so scenarios stay terse.
 */
export function authedHeaders(token, extra = {}) {
    return {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...extra,
        },
    };
}
