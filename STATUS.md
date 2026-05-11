# QualitéDomicile SaaS — Project Status & Model Design Reference

**Generated:** 2026-05-02 · **Last refreshed:** 2026-05-11
**Branch:** `feature/muma-setup` (91 commits ahead of `origin/main`)
**Test suite:** 868 test definitions across 130 test files
**Phase 1 backend:** ✅ closed at commit `ad4f939` — every IMPLEMENTATION_PLAN.txt §4 line item verified against code (see [§3.1](#31-phase-1-close-out-audit))
**Phase 2 backend:** ✅ all backend-deliverable spec rows closed — M3 / M6 / M4 / M5 / C1–C7 / E1–E5 done; M6.10 (official ISO/AFNOR fixture text — external content dependency) and M6.16 / M3.12 / M5.14 (Inertia pages — frontend team) are the only open items (see [PHASE2_PROGRESS.md](PHASE2_PROGRESS.md))
**Phase 2 tracker:** [PHASE2_PROGRESS.md](PHASE2_PROGRESS.md) — one checkbox per spec line, updated as work lands

Source of truth for product requirements: `CDC-QUALITE-DOM-2024-v2.0` (Cahier des Charges, Feb 2024)

---

## Table of Contents

1. [What QualitéDomicile Is](#1-what-qualitédomicile-is)
2. [Phase 0 — Foundation (complete)](#2-phase-0--foundation-complete)
3. [Phase 1 — Progress Map](#3-phase-1--progress-map)
4. [All Working HTTP Endpoints (web)](#4-all-working-http-endpoints-web)
5. [Mobile REST API — `/api/v1/*`](#5-mobile-rest-api--apiv1)
6. [Model Design — Intervention](#6-model-design--intervention)
7. [Model Design — Incident](#7-model-design--incident)
8. [Realtime — Reverb broadcast](#8-realtime--reverb-broadcast)
9. [Dashboard caching strategy](#9-dashboard-caching-strategy)
10. [Testing Strategy](#10-testing-strategy)
11. [Security Architecture](#11-security-architecture)
12. [Manual Testing Guide (Postman / browser)](#12-manual-testing-guide-postman--browser)
13. [What's Next — Phase 2 close-out & Phase 3 outlook](#13-whats-next--phase-2-close-out--phase-3-outlook)

---

## 1. What QualitéDomicile Is

A multi-tenant SaaS for French home-care organisations:

| Acronym | Full name |
|---------|-----------|
| SAAD | Service d'Aide et d'Accompagnement à Domicile |
| SSIAD | Service de Soins Infirmiers à Domicile |
| SPASAD | Service Polyvalent d'Aide et de Soins à Domicile |
| ESAD | Équipe Spécialisée Alzheimer à Domicile |
| CCAS | Centre Communal d'Action Sociale |

Each subscribing organisation is a **structure** (tenant). Two frontends share one backend:

- **Web admin** (Inertia + React) — coordinateurs, dirigeants, référents qualité, RH
- **Mobile** (React Native, offline-first) — field intervenants — served by `/api/v1/*` REST API ✅ **live since M4 W1**

**Tenancy model:** Row-level isolation via `structure_id` on every domain table + `BelongsToStructure` global scope. Cross-tenant rows are invisible at query time (404, not 403, on cross-tenant access).

**Compliance targets:** RGPD · HDS · ANSSI · Référentiel HAS

---

## 2. Phase 0 — Foundation (complete)

`structures` + `users` extended · `BelongsToStructure` trait · `StructureScope` (fails safe to `1=0`) · `TenantResolver` middleware · `CrossTenantQueryService` · Spatie `laravel-permission` with `team_foreign_key = structure_id` · `RoleSeeder` (6 roles, ~70 permissions) · `BasePolicy` · `BaseFormRequest` · `TenantAwareAudit` · `log_sensitive_read` middleware · Security headers (HSTS, CSP, X-Frame-Options…) · Rate limiters (api 60/min, login 5/min, two-factor 5/min, incident-declare 30/min, sync 20/min, mobile-api 300/min) · `/health/live` + `/health/ready` · Terraform scaffold for AWS Paris · all identifiers Anglicised (French kept for domain acronyms)

---

## 3. Phase 1 — Progress Map

| Month | Week | Scope | Status |
|-------|------|-------|--------|
| **M1** | W1–W4 | Beneficiaries · CarePlans · PlannedTasks · IntervenantAssignments | ✅ done |
| **M2** | W1 | Intervention model + state machine | ✅ done |
| **M2** | W2 | InterventionService · Controller · CheckIn/CheckOut/Cancel/SubmitReport | ✅ done |
| **M2** | W3 | InterventionPhotos + InterventionSignatures (S3 SSE-KMS + signed URLs) | ✅ done |
| **M2** | W4 | Reverb broadcast events + dashboard stats v1 (backend dispatches; FE listener deferred) | ✅ backend done |
| **M3** | W1 | Incident model + GraviteClassifier + ARS notification jobs | ✅ done |
| **M3** | W2 | IncidentService state machine + Controller + Form Requests | ✅ done |
| **M3** | W3 | DashboardStatsService Redis tag-cache + Observers + Scramble | ✅ done |
| **M3** | W4 | Hardening + k6 load test | ✅ done (lifecycle + sync 0% errors) |
| **M4** | W1 | **Mobile REST API `/api/v1/*` + Sanctum + idempotency** | ✅ done |
| **M4** | W2 | React Native skeleton (mobile team) | ⏳ deferred — mobile team scope |
| **M4** | W3 | Offline sync protocol (`/api/v1/sync/batch`) + LWW conflict markers + two-intervenants test | ✅ done at `ad4f939` |
| **M4** | W4 | Pilot onboarding | ⏳ deferred — business / ops scope |

### 3.1 Phase 1 close-out audit

Performed line-by-line on 2026-05-02 against IMPLEMENTATION_PLAN.txt §4. Every spec item below is now verified in code; the audit method was *grep + read*, not "I remember writing it."

| Spec line | Verification source |
|---|---|
| `beneficiaries` table + encrypted casts (M1 W1-2) | `app/Models/Beneficiary.php:78-81` |
| `BeneficiaryService::anonymize` (RGPD Art 17) | `app/Services/BeneficiaryService.php:89` |
| `CarePlanService::copyFromTemplate` (M1 W3) | `app/Services/CarePlanService.php:80` |
| Intervention check-in/check-out/cancel | `app/Services/InterventionService.php:53,78,101` |
| Intervention submit-report (M2 W1-2 "submit report") | `app/Services/InterventionService.php::submitReport` (added at `ad4f939`) |
| Intervention auto-cancel no-show | `app/Console/Commands/SweepMissedInterventionsCommand.php` (cron every 15min, `routes/console.php`) |
| S3 SSE-KMS uploads + 1-hour signed URLs (M2 W3) | `app/Services/InterventionMediaService.php:54,102,118` |
| Path-traversal + MIME-spoofing tests | `tests/Unit/Services/InterventionMediaServiceTest.php:61,82` |
| Incident model + categorie/gravite/statut enums | `app/Models/Incident.php` + `app/Enums/Gravite.php` |
| `GraviteClassifier` pure unit-tested | `tests/Unit/Services/GraviteClassifierTest.php` |
| 5-whys workflow (en_analyse → plan_actions) | `app/Services/IncidentService.php:121` |
| `NotifyResponsableSecteurJob` on every declare | `app/Services/IncidentService.php:89` |
| `NotifyARSJob` only on grave/critique | `app/Services/IncidentService.php:92` + `app/Jobs/NotifyARSJob.php:11` |
| Idempotency middleware on mutations | `app/Http/Middleware/HandleIdempotency.php` + applied via `'idempotent'` alias on writes |
| Dashboard Redis tag-cache, 5-min TTL, observer flush on writes | `app/Services/DashboardStatsService.php:18` + `app/Observers/InterventionObserver.php` + `app/Observers/IncidentObserver.php` |
| `/api/v1/*` routes + Sanctum + Scramble | `routes/api.php` + `config/sanctum.php` + `app/Providers/AppServiceProvider.php::configureScramble` |
| MFA-gated mobile token issuance | `app/Http/Controllers/Api/V1/AuthController.php:34-59` |
| Client-assigned UUIDs (M4 W3) | `app/Http/Requests/Api/V1/SyncBatchRequest.php:36` (`client_op_id` UUID required) |
| LWW + free-text conflict markers (M4 W3) | `app/Services/InterventionService.php::mergeReportText` + `tests/Unit/Services/InterventionReportMergeTest.php` |
| Two-intervenants race test (M4 W3) | `tests/Feature/Api/V1/SyncBatchTest.php` "preserves both narratives when an intervenant and a coordinateur race" |
| Cross-tenant leak test for every domain | `tests/Feature/Domain/{Beneficiaries,CarePlans,Interventions,Incidents,Assignments}/*TenantIsolationTest.php` |
| k6 load test (M3 W4) | dashboard / lifecycle / sync_batch scenarios — 0% errors |

### 3.2 Phase 1 — known deferrals (not engineering, not silent gaps)

| Deferred item | Type | Owner |
|---|---|---|
| Beneficiary detail page tabbed UX | Frontend | Web team — Phase 1 polish |
| Dashboard Chart.js / recharts visualisation | Frontend | Web team — Phase 1 polish |
| `useEcho` listener wiring for Reverb broadcasts | Frontend | Web team — Phase 1 polish |
| React Native app skeleton (M4 W2) | Mobile codebase | Mobile team — separate repo |
| External pentest with no critical findings | Security audit | Procurement / CISO |
| DR drill (RPO < 1h, RTO < 4h validation) | Ops | DevOps — schedule against staging |
| 10 pilot structures + 200 intervenants live | Sales / onboarding | Commercial team |
| NPS captured | Product | PM |

**These are real, named owners — not engineering gaps masquerading as "later".**

---

## 4. All Working HTTP Endpoints (web)

> Inside `auth + verified + tenant` middleware stack. Unauthenticated → redirect to `/login` · Cross-tenant access → `404` · Insufficient permission → `403`

### Health (unauthenticated)
| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/up` | ALB liveness probe |

### Beneficiaries
`GET POST /beneficiaries` · `GET /beneficiaries/create` · `GET PUT DELETE /beneficiaries/{id}` · `GET /beneficiaries/{id}/edit` · `GET /beneficiaries/{id}/dossier` (audit-logged)

### Care Plans
`GET POST /beneficiaries/{id}/care-plans` · `GET /beneficiaries/{id}/care-plans/create` · `GET PUT DELETE /care-plans/{id}` · `GET /care-plans/{id}/edit` · `POST /care-plans/{id}/{activate|archive|copy}`

### Planned Tasks
`POST /care-plans/{id}/tasks` · `PUT DELETE /tasks/{id}` (all blocked if plan archived → 403)

### Intervenant Assignments
`POST /beneficiaries/{id}/assignments` (409 if already active) · `DELETE /assignments/{id}` (409 if already closed)

### Interventions
| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/interventions` | List (intervenants see own only) |
| `GET POST` | `/interventions[/create]` · `/interventions/{id}[/edit]` | Standard CRUD |
| `POST` | `/interventions/{id}/checkin` | Planned → in_progress (records GPS + actual_start_at) |
| `POST` | `/interventions/{id}/checkout` | In-progress → completed |
| `POST` | `/interventions/{id}/cancel` | Cancel with required reason |
| `POST DELETE` | `/interventions/{id}/photos[/{photo}]` | S3 upload + signed-URL retrieval |
| `POST` | `/interventions/{id}/signatures` | Base64 PNG → S3 |

### Incidents
| Method | URL | Description |
|--------|-----|-------------|
| `GET POST` | `/incidents` | List + declare (auto-classifies gravity) |
| `GET PUT DELETE` | `/incidents/{id}` | Show / update analysis / soft-delete |
| `POST` | `/incidents/{id}/{assign\|launch-analysis\|close}` | State machine transitions |
| `POST` | `/incidents/{id}/actions` | Add corrective action |

### Dashboard
| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/dashboard` | Cached intervention + incident stats per structure (Redis tag-flush on writes) |

### OpenAPI docs
| `GET` | `/docs/api` | Auto-generated via Scramble (Bearer-secured), zero annotations needed |

---

## 5. Mobile REST API — `/api/v1/*`

**Auth:** Sanctum personal access tokens (Bearer header) · **Throttle:** 300 req/min per user (`mobile-api`) · **Idempotency:** `Idempotency-Key` header replays cached 2xx response for 24h on POST/PUT/PATCH/DELETE.

| Method | URL | Notes |
|--------|-----|-------|
| `POST` | `/api/v1/auth/login` | unauthenticated · throttle:login (5/min) |
| `POST` | `/api/v1/auth/logout` | revokes current token |
| `GET` | `/api/v1/auth/me` | returns UserResource |
| `GET` | `/api/v1/interventions[?date=YYYY-MM-DD]` | paginated 50, intervenants see own only |
| `GET` | `/api/v1/interventions/{id}` | nested beneficiary loaded |
| `POST` | `/api/v1/interventions/{id}/check-in` | idempotent · accepts `{latitude, longitude}` |
| `POST` | `/api/v1/interventions/{id}/check-out` | idempotent · accepts `{report_text}` |
| `POST` | `/api/v1/interventions/{id}/cancel` | idempotent |
| `POST DELETE` | `/api/v1/interventions/{id}/photos[/{photo}]` | idempotent · multipart upload |
| `POST` | `/api/v1/interventions/{id}/signatures` | idempotent · base64 PNG |
| `GET POST` | `/api/v1/incidents` | list + declare (auto-classified gravity + ARS dispatch) |
| `GET` | `/api/v1/incidents/{id}` | |
| `GET` | `/api/v1/beneficiaries` | paginated 100, ordered last/first name |
| `GET` | `/api/v1/beneficiaries/{id}` | |

All single-resource shows return the resource at the root (no `data` wrapper); collections paginate with the standard `{ data, links, meta }` envelope.

---

## 6. Model Design — Intervention

**File:** `app/Models/Intervention.php` · **Table:** `interventions`

### Lifecycle (state machine)
```
planned ──checkIn──► in_progress ──checkOut──► completed
   │                                                │
   └──cancel──► cancelled                           │
   │                                                │
   └──auto-miss──► missed                           │
                                                    │
                              isTerminal() ◄────────┘
```

`InterventionService` enforces transitions:
- `checkIn()` requires `isPlanned()` else 409 (and policy denies → 403)
- `checkOut()` requires `isInProgress()` else 409
- `cancel()` rejects if `isTerminal()`
- `update()` strips `structure_id` and `beneficiary_id` (no reparenting)

Each transition fires `InterventionStatusChanged` (see §8).

### Media submodels
- `InterventionPhoto` (UUID PK · structure_id · disk · path · mime · size_bytes · uploaded_by) — **no SoftDeletes** (S3 delete must be physical)
- `InterventionSignature` (UUID PK · structure_id · path · signer_type · signed_by · signed_at)
- `InterventionMediaService` — MIME whitelist (jpeg/png/webp), 5MB cap, signed URLs (1h TTL), SSE-KMS at rest

---

## 7. Model Design — Incident

**File:** `app/Models/Incident.php` · **Table:** `incidents` · `Auditable` (description encrypted)

### Lifecycle
```
declare ──assign──► en_analyse ──launchAnalysis──► plan_actions ──close──► clos
```

### GraviteClassifier (pure static)
Per CDC §6.2 — runs at declaration time, zero DB:

| Trigger | Result |
|---------|--------|
| `avec_deces = true` | `critique` (overrides all) |
| `MaltraitanceSuspecte` or `SituationDanger` | `critique` |
| `avec_hospitalisation` or `ErreurMedicamenteuse` | `grave` |
| `avec_blessure_physique` or `Agression` | `significatif` |
| default | `mineur` |

### Async jobs
- `NotifyResponsableSecteurJob` — fires on every declare; idempotent on `notifie_responsable_at`
- `NotifyARSJob` — fires only when `gravite->requiresARSNotification()` (grave/critique); idempotent on `notifie_ars_at`

`IncidentDeclared` event broadcasts on `private-structure.{id}` so dashboards refresh in realtime.

---

## 8. Realtime — Reverb broadcast

**Channel:** `private-structure.{structureId}` (auth gate in `routes/channels.php` checks `$user->structure_id === $structureId`)

| Event | broadcastAs | Fired when |
|-------|-------------|-----------|
| `InterventionStatusChanged` | `intervention.status.changed` | check-in / check-out / cancel |
| `IncidentDeclared` | `incident.declared` | any new incident |

The mobile/web client subscribes to its tenant's private channel and updates UI without polling.

---

## 9. Dashboard caching strategy

**Pattern:** Redis tag-based cache, 5-min TTL, observer-driven invalidation.

```php
Cache::tags(["structure:{$id}:dashboard"])->remember('stats', 300, fn() => …);
```

`InterventionObserver` and `IncidentObserver` flush the tenant's tag on `saved` / `deleted` / `restored`. Subsequent dashboard hits within the TTL serve from Redis with **0 DB queries** (verified by Pest test).

`DashboardStatsService::stats($structureId)` returns:
- `interventions_today`, `interventions_ce_mois`, `in_progress_count`, `completed/cancelled/missed_today`
- `incidents_declares`, `incidents_en_cours`, `incidents_graves`, `incidents_ce_mois`

`recentIncidents()` is intentionally **not cached** — always fresh.

---

## 10. Testing Strategy

**Framework:** Pest (NOT PHPUnit — overrides Laravel Boost default)
**Rule:** Every feature ships with both a feature test AND a unit test.
**Current:** 868 test definitions across 130 test files (Phase 1 + Phase 2 M3/M4/M5/M6 + Billing C1–C7 + E1–E4)

### Layout
```
tests/
├── Feature/
│   ├── Api/V1/                    ← M4 W1 (33 tests)
│   │   ├── AuthApiTest.php        login, logout, /me, token rotation
│   │   ├── InterventionApiTest.php CRUD, lifecycle, idempotency replay, cross-tenant
│   │   ├── IncidentApiTest.php    declare, gravity auto-classify, leak test
│   │   └── BeneficiaryApiTest.php list, show, leak test
│   ├── Domain/Beneficiaries/*     M1
│   ├── Domain/CarePlans/*         M1
│   ├── Domain/PlannedTasks/*      M1
│   ├── Domain/Assignments/*       M1
│   ├── Domain/Interventions/      M2
│   │   ├── InterventionHttpTest, InterventionMediaTest, InterventionEventsTest
│   │   └── InterventionTenantIsolationTest
│   ├── Domain/Incidents/          M3
│   │   ├── IncidentHttpTest, IncidentTenantIsolationTest
│   ├── Auditing, Rbac, Tenancy, Middleware, RateLimiting, HealthCheckTest
└── Unit/Services/
    ├── BeneficiaryServiceTest, CarePlanServiceTest
    ├── IntervenantAssignmentServiceTest, PlannedTaskServiceTest
    ├── InterventionServiceTest                M2
    ├── GraviteClassifierTest, IncidentServiceTest    M3
    └── DashboardStatsServiceTest              M3 (cache-hit test asserts 0 DB queries)
```

### Helpers (`tests/Pest.php`)
- `twoStructures()` — bootstraps two tenants + coordinateur users with roles assigned
- `actingAsRole($role)` — session auth + binds tenant context + Spatie team_id
- `actingAsApiRole($role)` — same but `actingAs($user, 'sanctum')` for `/api/v1/*` tests

---

## 11. Security Architecture

```
Request
  ├─ 1. Tenant scope     (BelongsToStructure global scope; foreign rows → 404)
  ├─ 2. Policy           (BasePolicy::before checks ownership; 403 on denial)
  ├─ 3. Form Request     (authorize() + validation; 422 with error bag)
  ├─ 4. Service          (DB::transaction + business invariants; 409/422)
  └─ 5. Audit log        (Auditable trait — automatic on create/update/delete)
```

### HDS Compliance Checklist

| Item | Status |
|------|--------|
| Encrypted health data at rest | ✅ `'encrypted'` cast on medical fields + incident description |
| Audit trail on all health data reads/writes | ✅ Auditable + `log_sensitive_read` |
| MFA (TOTP via Fortify) | ✅ Mandatory for coordinateurs+ |
| Rate limiting on auth endpoints | ✅ 5/min login, 5/min two-factor |
| Security headers (HSTS, CSP, X-Frame-Options) | ✅ |
| Row-level multi-tenancy | ✅ Zero cross-tenant leakage (tested across 13 leak tests) |
| Soft deletes (legal retention) | ✅ All domain models except media |
| S3 SSE-KMS encryption + signed URLs | ✅ M2 W3 |
| Idempotency on mobile writes | ✅ M4 W1 — Redis 24h replay |
| Realtime tenant-scoped broadcast | ✅ M2 W4 (private-structure.{id}) |
| Reverb / WebSocket auth | ✅ channel guard in `routes/channels.php` |

---

## 12. Manual Testing Guide — step-by-step

Covers every feature shipped through Phase 1 M4 W1. Follow §12.3 for the web app and §12.4 for the mobile REST API. All steps assume the server is on `http://127.0.0.1:8000`.

### 12.1 One-time setup

```bash
docker compose up -d                           # Postgres 16 + Redis 7
php artisan migrate:fresh                      # rebuilds the schema
php artisan db:seed --class=DevSeeder          # 4 demo users + 5 bénéficiaires + 15 interventions
composer run dev                               # php serve + vite + queue in one command
```

If `composer run dev` is unavailable, run each in its own terminal: `php artisan serve`, `npm run dev`, `php artisan queue:work`.

### 12.2 Demo accounts (password: `password`)

| Email | Role | Use for |
|-------|------|---------|
| `dirigeant@demo.fr` | dirigeant | full access incl. deletions |
| `coordinateur@demo.fr` | coordinateur | **main test account** — planning, CRUD |
| `qualite@demo.fr` | referent_qualite | read + audit |
| `intervenant@demo.fr` | intervenant | mobile API — sees own interventions only |

MFA (TOTP) is **not enforced** in dev — you can log straight in with email + password.

### 12.3 Web UI walkthrough (exercises M1–M3)

Open `http://127.0.0.1:8000/login` in a browser.

1. **Log in** as `coordinateur@demo.fr` / `password`.
2. **Dashboard** (`/dashboard`) — intervention and incident stat tiles. Cached 5 min per tenant (Redis tag-flushed on writes).
3. **Beneficiaries** (`/beneficiaries`)
   - List shows the 5 seeded rows.
   - **New** → create one (first name, last name, date of birth, address required).
   - **Edit** / **Delete** (soft).
   - Open `/beneficiaries/{id}/dossier` — the detailed view writes an audit entry via `log_sensitive_read`.
4. **Care Plans** — from a beneficiary's page: **New Care Plan**.
   - Fill the form, save (state = `draft`).
   - **Activate** → `active`. **Archive** → `archived` (tasks become read-only). **Copy** → duplicates into a fresh `draft`.
5. **Planned Tasks** — inline on the care-plan page.
   - Add: title, frequency (daily / weekly / one-off), time-of-day.
   - Edit / delete any task.
   - Try adding a task to an **archived** plan → `403`.
6. **Intervenant Assignments** — on a beneficiary page, assign `intervenant@demo.fr`.
   - Assigning the same intervenant again while the previous is still active → `409`.
   - Close the assignment, then try to close it again → `409`.
7. **Interventions** (`/interventions`)
   - List shows the 15 seeded rows.
   - **New** → create one (beneficiary + intervenant + scheduled time).
   - Click **Check-in** on a `planned` intervention → transitions to `in_progress`, records `actual_start_at` (and GPS if provided).
   - **Upload photo** on the intervention page — JPG/PNG/WebP ≤ 5MB. Retrieval is via a 1-hour signed S3 URL.
   - **Upload signature** — beneficiary signature canvas.
   - **Check-out** → requires `report_text`; status becomes `completed`, fires `intervention.status.changed` on the tenant Reverb channel.
   - On a different intervention, **Cancel** with a reason → `cancelled`.
8. **Incidents** (`/incidents`)
   - **Declare** → category, description, location, and at least one of: `avec_deces`, `avec_hospitalisation`, `avec_blessure_physique`.
   - Gravity is **auto-classified** server-side by `GraviteClassifier` — no manual gravity field is exposed.
   - If `grave` or `critique`, a `NotifyARSJob` is queued (run `php artisan queue:work` in another terminal to watch it fire).
   - `IncidentDeclared` broadcasts on the tenant channel.
9. **Incident lifecycle** — from the incident show page:
   - **Assign to someone** → `en_analyse`
   - **Launch Analysis** → `plan_actions`
   - **Add Corrective Action**
   - **Close** → `clos`
10. **Dashboard refresh** — back to `/dashboard`; counts reflect everything above. Cache hit on reload serves with 0 DB queries (verified by the Pest test in `DashboardStatsServiceTest`).
11. **Log out** from the top-right menu.

### 12.4 Mobile REST API walkthrough — Postman (exercises M4 W1)

> The mobile API lives at **`/api/v1/*`**. The Fortify `/login` URL is web-only and CSRF-protected — sending JSON there returns 419.

**Step 1 — Log in** (no auth needed)

```
POST http://127.0.0.1:8000/api/v1/auth/login
Content-Type: application/json

{ "email": "coordinateur@demo.fr", "password": "password" }
```

Response → `{ "token": "1|…", "user": {…} }`. Copy the token.

In Postman: **Authorization tab → Bearer Token → paste**. All subsequent steps assume this header is set.

**Step 2 — Current user**

```
GET /api/v1/auth/me
```

**Step 3 — List interventions** (paginated 50)

```
GET /api/v1/interventions
GET /api/v1/interventions?date=2026-04-24       # filter by scheduled date
```

Log in as `intervenant@demo.fr` instead → only their own interventions are returned (policy check).

**Step 4 — Show one** (includes beneficiary)

```
GET /api/v1/interventions/{id}
```

**Step 5 — Check-in**

```
POST /api/v1/interventions/{id}/check-in
Content-Type: application/json

{ "latitude": "48.8566", "longitude": "2.3522" }
```

Transitions `planned → in_progress`. Attempting check-in on a non-planned one → `403`.

**Step 6 — Check-out**

```
POST /api/v1/interventions/{id}/check-out
{ "report_text": "Visite bien déroulée." }
```

**Step 7 — Cancel** (terminal transitions are blocked — must not be already completed)

```
POST /api/v1/interventions/{id}/cancel
{ "cancellation_reason": "Bénéficiaire hospitalisé." }
```

**Step 8 — Upload photo** (multipart/form-data)

In Postman: **Body → form-data** → key `file` (type **File**) → choose a JPG/PNG/WebP ≤ 5MB.

```
POST /api/v1/interventions/{id}/photos
```

Response includes the photo record + 1-hour signed S3 URL.

**Step 9 — Upload signature** (base64 PNG)

```
POST /api/v1/interventions/{id}/signatures
{
  "signer_type": "beneficiary",
  "signature_base64": "iVBORw0KGgoAAAANSUhEUg..."
}
```

**Step 10 — List incidents**

```
GET /api/v1/incidents
```

**Step 11 — Declare incident**

```
POST /api/v1/incidents
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json

{
  "occurred_at": "2026-04-24T08:30:00Z",
  "categorie": "chute",
  "description": "Le bénéficiaire est tombé dans la cuisine.",
  "lieu": "Cuisine",
  "avec_blessure_physique": true
}
```

Response carries `gravite: "significatif"` (auto-classified). Re-declare with `"avec_deces": true` → `gravite: "critique"` and `requires_ars_notification: true`.

**Step 12 — Show incident**

```
GET /api/v1/incidents/{id}
```

**Step 13 — List / show beneficiaries**

```
GET /api/v1/beneficiaries
GET /api/v1/beneficiaries/{id}
```

**Step 14 — Idempotency replay** (retry-safe mobile writes)

Pick any POST from above. Send it with `Idempotency-Key: <uuid>`. Send it again with the **same** key. The second response:

- Has the identical status + body
- Carries `X-Idempotent-Replayed: true`
- Does not re-execute server-side (check Postgres — no new row)

Cached for 24 h in Redis.

**Step 15 — Log out**

```
POST /api/v1/auth/logout
```

Revokes the current token. Any subsequent `/api/v1/*` call → `401`.

### 12.5 OpenAPI docs — Scramble

Open `http://127.0.0.1:8000/docs/api`. Every `/api/v1/*` route is listed with request/response schemas. Click **Try It** on any endpoint — paste the Bearer token into the auth panel first. File uploads (photos) work better in Postman than in the Scramble UI.

### 12.6 Architecture smoke tests (2 minutes, very satisfying)

- **Cross-tenant isolation** — log in from Postman as tenant A, save an intervention ID; log in as tenant B (a different `structure_id`), `GET /api/v1/interventions/{that-id}` → `404`, **not** `403`. Foreign rows are invisible, not forbidden.
- **Unauthenticated** — delete the Bearer header, hit any `/api/v1/*` → `401`.
- **Login rate limit** — send `POST /api/v1/auth/login` with wrong credentials 6× in a minute → the 6th returns `429`.
- **Policy vs service rejection** — log in as `intervenant@demo.fr`, try to `POST /api/v1/interventions/{foreign-id}/check-in` → `403` (policy). Try to check in one that's already `completed` → `403` (policy denies before the service can return 409 — a known quirk, documented in `InterventionApiTest`).

### 12.7 Run the automated suite

```bash
php artisan test --compact                                  # 263 / 738 — full suite
php artisan test --compact tests/Feature/Api/V1/            # mobile API only (33 tests)
php artisan test --compact --filter='idempotency'           # single behaviour
```

### 12.8 Reset between sessions

```bash
php artisan migrate:fresh --seed                            # nukes + reseeds default
php artisan migrate:fresh && php artisan db:seed --class=DevSeeder   # same but only the DevSeeder set
```

Redis cache entries are tenant-tagged and flush themselves as data changes — no manual Redis action needed.

---

## 13. What's Next — Phase 2 close-out & Phase 3 outlook

> Phase 1 (Months 1–4) is sealed. Phase 1 M4 W2/W4 (RN skeleton + pilot onboarding) are not engineering — owned by mobile and commercial teams respectively. M4 W3 (offline sync) shipped at `ad4f939`.

### 13.1 Phase 2 engineering — status (2026-05-11)

**All backend-deliverable Phase 2 spec rows are closed.** The table below summarises the final state of every engineering concern and module block.

| Block | Status | Notes |
|---|---|---|
| **M3** QVCT | ✅ closed | 30/30 backend rows |
| **M4** Communication | ✅ closed | 21/22 backend rows; mark-read (`M4.9`) shipped `a04cf59`; M4.19 FE listener deferred to frontend team |
| **M5** Compétences | ✅ closed | 19/20 backend rows; M5.14 Inertia pages deferred to frontend team |
| **M6** Audits | ✅ closed | 23/25 backend rows; M6.10 ISO/AFNOR official content is an external content dependency; M6.16 Inertia pages deferred |
| **C1–C7** Billing | ✅ closed | Cashier + Stripe + circuit-breaker protection |
| **E1** APM | ✅ closed | Sentry Performance + Telescope; Datadog deferred pending procurement / DPA |
| **E2** Circuit breakers | ✅ closed | Redis-backed `CircuitBreaker` on `NotifyARSJob`; reusable primitive |
| **E3** Feature flags | ✅ closed | `QvctWeakSignalAlerts` Pennant flag — first production kill switch |
| **E4** Access review | ✅ closed | `access-review:export` command; quarterly cron scheduled |
| **E5** HDS cert docs | deliberately open | Non-code CISO deliverable — see `PHASE2_PROGRESS.md` §E5 |
| **M6.9** AuditGridLibrary | ✅ closed | Service + ISO/AFNOR stubs + `audit-grids:provision` command (`dcc298f`) |
| **M6.19** HAS prep guide | ✅ closed | `HASPreparationService` + API endpoint (`2ae1709`) |
| **M4.9** mark-read | ✅ closed | `message_read_cursors` cursor watermark + API (`a04cf59`) |

**Remaining open items (not engineering blockers):**
- **M6.10** — ISO 9001 + AFNOR NF X50-056 official fixture content: stub JSONs are in place; a compliance officer must replace item text with purchased standard wording before pilot use of those grids.
- **Inertia pages** (M6.16, M3.12, M5.14) — frontend team's slice; backend services and API are fully ready.
- **P1-D1/D2/D3** — frontend carry-overs.
- **P1-D4/D5** — conditional on spatial / 5-whys analytics requirements (not yet triggered).
- **E5** — CISO/compliance deliverable.

### 13.2 Branch hygiene (action required)

`feature/muma-setup` is **91 commits ahead of `origin/main`**. The Phase 2 backend must land via reviewable PRs before Phase 2 acceptance can be claimed end-to-end. Suggested PR split:

1. M3 QVCT (M3.1–M3.12 backend)
2. M6 Audits (M6.1–M6.25 backend, including M6.9 / M6.19 / M6.20)
3. M4 Communication (M4.1–M4.19 backend + M4.9 mark-read)
4. M5 Compétences (M5.1–M5.14 backend)
5. Billing C1–C7
6. Engineering concerns E1–E4 sweep

### 13.3 Phase 2 acceptance (commercial / ops)

Per [IMPLEMENTATION_PLAN.txt:464-470](IMPLEMENTATION_PLAN.txt#L464-L470). Backend is ready; the remaining items are commercial / ops milestones — not engineering:

- 50 pilot structures (from 10) — sales / onboarding
- Pro tier billing live with paying customers — gated on landing C1–C7 to `main`
- QVCT baromètre running with measurable weak-signal alerts in pilots — pilot ops + product
- First HAS évaluation externe prepared via the platform — pilot customer + référent qualité
- 99.9% uptime — DevOps, observability dashboards must back this number once APM matures

### 13.4 Phase 3 (Months 9–14) — Premium / AI outlook

Not started. Scope per [IMPLEMENTATION_PLAN.txt:473-520](IMPLEMENTATION_PLAN.txt#L473-L520):

- **M9 IA prédictive** — Python microservice (scikit-learn + transformers + LLM API). Burnout risk, autonomy-loss detection, preventive action suggestions, semantic analysis of intervention reports. Laravel queues ML jobs; results written back to a PostgreSQL analytics schema. Fallback: "en calcul" placeholder when the service is down — never error out the main flow.
- **M8 Portail bénéficiaires** — separate Inertia (or Next.js PWA) app for bénéficiaires + familles. Care plan + intervention history + satisfaction survey + direct incident reporting. Anonymous tokens (signed URL access with limited scope) for family members.
- **Benchmark anonymisé** — the ONE approved usage of `CrossTenantQueryService`: aggregate anonymised indicators across all comparable-size structures, monthly sector report. Every access logged + auditable.
- **Reporting engine** — annual quality report auto-generated per structure (PDF), sent to authorities as required.

Engineering concerns scaled in Phase 3: read replicas for analytics, table partitioning on `interventions` / `audit_logs` / `qvct_reponses` (by structure_id, date), layered caching (Redis hot + CDN edge), contract tests run in CI against the OpenAPI spec.
