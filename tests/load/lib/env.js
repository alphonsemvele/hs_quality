// Shared k6 env configuration. Override per-scenario via env vars on the CLI.
//
//   k6 run -e BASE_URL=https://staging.qualitedomicile.fr ...
//
// Defaults target a local Docker compose + `php artisan serve` setup.

export const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
export const API_BASE = `${BASE_URL}/api/v1`;

// Default seeded credentials (DevSeeder + StructureSeeder). Override in
// staging/prod via env so we never bake real secrets into the scripts.
export const SEED_USERS = {
    intervenant: {
        email: __ENV.K6_INTERVENANT_EMAIL || 'intervenant@demo.fr',
        password: __ENV.K6_INTERVENANT_PASSWORD || 'password',
    },
    coordinateur: {
        email: __ENV.K6_COORDINATEUR_EMAIL || 'coordinateur@demo.fr',
        password: __ENV.K6_COORDINATEUR_PASSWORD || 'password',
    },
    dirigeant: {
        email: __ENV.K6_DIRIGEANT_EMAIL || 'dirigeant@demo.fr',
        password: __ENV.K6_DIRIGEANT_PASSWORD || 'password',
    },
};

// SLA targets (Phase 1 acceptance — IMPLEMENTATION_PLAN.txt:362-364).
//   - Read endpoints: p95 < 500ms
//   - Write endpoints: p95 < 1000ms
//   - Error rate (4xx + 5xx + dropped): < 1%
//
// k6 scenarios reference these so all scripts share the same bar.
export const SLA = {
    READ_P95_MS: 500,
    WRITE_P95_MS: 1000,
    ERROR_RATE_MAX: 0.01,
};
