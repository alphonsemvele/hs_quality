# Load tests (k6)

Phase 1 acceptance — `IMPLEMENTATION_PLAN.txt:362-364`:
> Load test (k6) simulating 200 intervenants × 5 interventions/day at peak
> concurrency; verify p95 < 500ms on dashboard.

These scripts are committed to the repo so they run identically on any
environment. Whoever executes them just needs `k6` on the path and a
populated database.

## Install k6

| OS | Command |
|----|---------|
| Ubuntu / Debian | `sudo gpg -k && sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69 && echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \| sudo tee /etc/apt/sources.list.d/k6.list && sudo apt-get update && sudo apt-get install k6` |
| macOS | `brew install k6` |
| Docker | `docker run --rm -i -v "$PWD":/work -w /work grafana/k6 run tests/load/scenarios/smoke.js` |

## Seed the database

The scripts assume the `DevSeeder` accounts exist plus 200 planned
interventions for `intervenant@demo.fr`:

```
docker compose up -d                             # postgres / redis / minio
php artisan migrate:fresh
php artisan db:seed --class=DevSeeder
php artisan db:seed --class=LoadTestSeeder
php artisan serve                                # OR sail up if you use Sail
```

Boot Reverb in another shell if you want WebSocket events to actually
push during the load test (otherwise broadcasts are dispatched but
discarded silently):

```
php artisan reverb:start
```

## Run via Docker (no install)

The simplest path — works on any machine with Docker:

```
# Linux: --add-host=host.docker.internal:host-gateway lets the container
# reach the host's `php artisan serve` on port 8000.
docker run --rm \
  --add-host=host.docker.internal:host-gateway \
  -v "$PWD/tests/load:/work" -w /work \
  grafana/k6:latest run \
  -e BASE_URL=http://host.docker.internal:8000 \
  scenarios/smoke.js
```

(macOS already has `host.docker.internal` — the `--add-host` flag is a no-op.)

## Run the scenarios

| Scenario | Time | Purpose |
|----------|------|---------|
| `smoke.js` | ~5s | Sanity check — does login + 3 reads work? Run before any other scenario. |
| `login_burst.js` | ~40s | 100 logins / 30s. Validates `throttle:login` (5/min/IP+email) holds, no 5xx under burst. |
| `dashboard_p95.js` | ~5min | 50 concurrent coordinateurs hammer dashboard reads. **The plan-mandated SLA — p95 < 500ms.** |
| `intervention_lifecycle.js` | ~10min | 200 VU peak running login → list → check-in → check-out. Models the morning visit window. |
| `sync_batch_flush.js` | ~2min | 50 mobile clients flush a 50-op batch each. Models reconnect-after-offline burst. |

Run one:
```
k6 run tests/load/scenarios/smoke.js
```

Run against staging:
```
k6 run -e BASE_URL=https://staging.qualitedomicile.fr tests/load/scenarios/dashboard_p95.js
```

Override seeded credentials:
```
k6 run \
  -e BASE_URL=https://staging.qualitedomicile.fr \
  -e K6_INTERVENANT_EMAIL=loadtest1@example.fr \
  -e K6_INTERVENANT_PASSWORD=... \
  tests/load/scenarios/intervention_lifecycle.js
```

## Empirical results from the local Docker run

`smoke.js` and `login_burst.js` were validated against `php artisan serve`
+ Docker postgres/redis on a dev workstation (no install of k6 needed,
ran via `grafana/k6` Docker image):

| Scenario | Outcome | Notes |
|----------|---------|-------|
| `smoke.js` | ✅ 6/6 checks, 0 errors | First request ~1.2s (artisan serve cold start); rest <500ms |
| `login_burst.js` | ✅ rate limiter holds | `login_accepted=5`, `login_throttled=35` — exactly matches `throttle:login` (5/min/IP+email). 0 server errors. Empirical proof the C2/M9 protections work under burst |

The `dashboard_p95.js` and `intervention_lifecycle.js` scripts will saturate
`php artisan serve` (single-threaded) and aren't meaningful as SLA
validations on a dev workstation. Run them against staging with prod-like
sizing (php-fpm + nginx + multiple workers) to validate the plan's
"p95 < 500ms" target.

## Interpreting the output

k6 prints summary metrics at the end. The thresholds above each scenario
fail the run if breached — exit code is non-zero so CI can gate on it.

Key metrics per scenario:

| Metric | Source | Pass criterion |
|--------|--------|----------------|
| `http_req_duration{name:dashboard.interventions} p(95)` | `dashboard_p95.js` | < 500ms |
| `http_req_failed rate` | all | < 1% |
| `login_throttled` counter | `login_burst.js` | most after 1st few; near-zero would mean the limiter is OFF |
| `intervention_check_in_ok` counter | `intervention_lifecycle.js` | should equal VU iterations |

## What this DOESN'T validate

- **Real production network latency** — local runs measure best-case
- **CDN / edge cache behavior** — there is no CDN in front of the API
- **Mail delivery throughput** — `MAIL_MAILER=array` in tests; real SMTP
  pressure isn't modelled
- **S3 upload throughput at scale** — photo uploads aren't in the load
  test (would need binary fixtures + multipart)

For pre-pilot acceptance: run `dashboard_p95.js` and
`intervention_lifecycle.js` against staging with prod-like sizing. If
both pass their thresholds, the plan's SLA is satisfied.

## Cleanup

`LoadTestSeeder` doesn't tag the bulk records, so the simplest cleanup is:

```
php artisan migrate:fresh --seed   # nukes everything; then re-DevSeed
```

Never run `LoadTestSeeder` against a database holding real customer data.
