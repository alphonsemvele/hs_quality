# QualitéDomicile SaaS — Project Status & Model Design Reference

**Generated:** 2026-04-20  
**Branch:** `feature/muma-setup`  
**Last commit:** `c502aac`  
**Test suite:** 139 tests · 485 assertions · **ALL GREEN**

Source of truth for product requirements: `CDC-QUALITE-DOM-2024-v2.0` (Cahier des Charges, Feb 2024)

---

## Table of Contents

1. [What QualitéDomicile Is](#1-what-qualitédomicile-is)
2. [Phase 0 — Foundation (complete)](#2-phase-0--foundation-complete)
3. [Phase 1 Month 1 — Core Entities (complete)](#3-phase-1-month-1--core-entities-complete)
4. [All Working HTTP Endpoints](#4-all-working-http-endpoints)
5. [Model Design: Beneficiary](#5-model-design--beneficiary)
6. [Model Design: CarePlan](#6-model-design--careplan)
7. [Model Design: PlannedTask](#7-model-design--plannedtask)
8. [Model Design: IntervenantAssignment](#8-model-design--intervenantassignment)
9. [Testing Strategy](#9-testing-strategy)
10. [Security Architecture](#10-security-architecture)
11. [Manual API Testing Guide](#11-manual-api-testing-guide)
12. [What's Next — Phase 1 Month 2+](#12-whats-next--phase-1-month-2)

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
- **Mobile** (React Native, offline-first) — field intervenants — served by `/api/v1/*` REST API *(not yet built)*

**Tenancy model:** Row-level isolation via `structure_id` on every domain table + `BelongsToStructure` global scope. Cross-tenant rows are invisible at query time (404, not 403, on cross-tenant access).

**Compliance targets:** RGPD · HDS · ANSSI · Référentiel HAS

---

## 2. Phase 0 — Foundation (complete)

| Commit | What was built |
|--------|---------------|
| `430e869` | `CLAUDE.md` locked rules · custom `qualite-domicile-backend` skill |
| `4e5aacf` | `structures` table · `BelongsToStructure` trait · `StructureScope` (fails safe: `whereRaw('1 = 0')` when no tenant bound) · `TenantMiddleware` · `CrossTenantQueryService` · Pest helpers (`actingAsRole`, `actingAsStructure`, `twoStructures`) |
| `65f34a8` | `users` table extended (`structure_id`, `type` enum) · Spatie `laravel-permission` with `team_foreign_key = structure_id` · `RoleSeeder` (6 roles, ~70 permissions) · `BasePolicy` · `BaseFormRequest` |
| `e6bd1e7` | `TenantAwareAudit` model · `log_sensitive_read` middleware · Security headers (HSTS, CSP, X-Frame-Options…) · Rate limiters (api 60/min, login 5/min, two-factor 5/min, incident-declare 30/min, sync 20/min) |
| `5bd2d77` | `/health/live` · `/health/ready` · Terraform scaffold for AWS Paris (ECS + RDS + ElastiCache + S3 + CloudFront + ALB) |
| `3206e11` | All French code identifiers translated to English (French kept for domain acronyms: gir, qvct, pac, ssiad…) |

---

## 3. Phase 1 Month 1 — Core Entities (complete)

| Commit | What was built |
|--------|---------------|
| `34c478a` | `beneficiaries` table · `Beneficiary` model (HasUuids, BelongsToStructure, Auditable, SoftDeletes, encrypted medical fields) · `BeneficiaryPolicy` · `BeneficiaryService` (create, update, anonymize) · `BeneficiaryResource` / `BeneficiaryDossierResource` · factory |
| `a95c322` | `BeneficiaryController` (index, create, store, show, edit, update, destroy, dossier) · Inertia pages · Routes |
| `96b0882` | `care_plans` table · `planned_tasks` table · `CarePlan` model · `PlannedTask` model (structure_id mirrored from parent via `booted()` listener) · `CarePlanPolicy` · `CarePlanService` (create, update, activate, archive, copyFromTemplate) · factories |
| `a636f10` | `CarePlanController` (indexForBeneficiary, createForBeneficiary, storeForBeneficiary, show, edit, update, destroy, activate, archive, copy) · Inertia pages · Routes |
| `e039944` | `intervenant_assignments` table (partial unique index on active assignments) · `IntervenantAssignment` model (NO SoftDeletes — history must persist) · `IntervenantAssignmentService` (assign, unassign) · User model extended with assignment relations |
| `450300e` | `AttachIntervenantRequest` · `IntervenantAssignmentResource` · `IntervenantAssignmentPolicy` · `AssignmentController` (store, destroy) · Routes · BeneficiaryController show() enhanced with assignment data |
| `f6e89e1` | `PlannedTaskService` (create, update, delete + archived guard) · `PlannedTaskPolicy` · `StorePlannedTaskRequest` / `UpdatePlannedTaskRequest` · `PlannedTaskController` · Routes · 19 Pest tests |

---

## 4. All Working HTTP Endpoints

> All endpoints are inside the `auth + verified + tenant` middleware stack.  
> Unauthenticated → redirect to `/login` · Cross-tenant access → `404` · Insufficient permission → `403`

### Health (unauthenticated)

| Method | URL | Description |
|--------|-----|-------------|
| `GET` | `/health/live` | ALB liveness probe |
| `GET` | `/health/ready` | Readiness probe (DB + Redis + S3) |

### Beneficiaries

| Method | URL | Auth required | Description |
|--------|-----|---------------|-------------|
| `GET` | `/beneficiaries` | `beneficiaries.viewAny` | List (intervenants: only assigned) |
| `GET` | `/beneficiaries/create` | `beneficiaries.create` | Create form |
| `POST` | `/beneficiaries` | `beneficiaries.create` | Store |
| `GET` | `/beneficiaries/{id}` | `BeneficiaryPolicy::view` | Show (no health data) |
| `GET` | `/beneficiaries/{id}/edit` | `BeneficiaryPolicy::update` | Edit form |
| `PUT` | `/beneficiaries/{id}` | `BeneficiaryPolicy::update` | Update |
| `DELETE` | `/beneficiaries/{id}` | `BeneficiaryPolicy::delete` | Soft-delete |
| `GET` | `/beneficiaries/{id}/dossier` | `BeneficiaryPolicy::view` + `log_sensitive_read` | Full encrypted health dossier (access-logged) |

### Care Plans

| Method | URL | Auth required | Description |
|--------|-----|---------------|-------------|
| `GET` | `/beneficiaries/{id}/care-plans` | `CarePlanPolicy::viewAny` | List plans for beneficiary |
| `GET` | `/beneficiaries/{id}/care-plans/create` | `CarePlanPolicy::create` | Create form |
| `POST` | `/beneficiaries/{id}/care-plans` | `CarePlanPolicy::create` | Store |
| `GET` | `/care-plans/{id}` | `CarePlanPolicy::view` | Show (tasks read-only) |
| `GET` | `/care-plans/{id}/edit` | `CarePlanPolicy::update` | Edit form |
| `PUT` | `/care-plans/{id}` | `CarePlanPolicy::update` | Update (blocked if archived) |
| `DELETE` | `/care-plans/{id}` | `CarePlanPolicy::delete` (dirigeant) | Soft-delete |
| `POST` | `/care-plans/{id}/activate` | `CarePlanPolicy::update` | Activate (auto-archives current active plan) |
| `POST` | `/care-plans/{id}/archive` | `CarePlanPolicy::archive` | Archive (requires reason string) |
| `POST` | `/care-plans/{id}/copy` | `CarePlanPolicy::create` | Copy as new draft template |

### Planned Tasks

| Method | URL | Auth required | Description |
|--------|-----|---------------|-------------|
| `POST` | `/care-plans/{id}/tasks` | `PlannedTaskPolicy::create` | Add task (blocked if plan archived → 403) |
| `PUT` | `/tasks/{id}` | `PlannedTaskPolicy::update` | Update task (blocked if plan archived → 403) |
| `DELETE` | `/tasks/{id}` | `PlannedTaskPolicy::delete` | Soft-delete task (blocked if plan archived → 403) |

### Intervenant Assignments

| Method | URL | Auth required | Description |
|--------|-----|---------------|-------------|
| `POST` | `/beneficiaries/{id}/assignments` | `BeneficiaryPolicy::update` | Assign intervenant (409 if already active) |
| `DELETE` | `/assignments/{id}` | `IntervenantAssignmentPolicy::delete` | Unassign (sets `unassigned_at`; 409 if already closed) |

---

## 5. Model Design — Beneficiary

**File:** `app/Models/Beneficiary.php`  
**Table:** `beneficiaries`

### Purpose

The care recipient — the person at the centre of every workflow. Contains the highest-sensitivity data in the system (RGPD Art 9 special category — health data).

### Key Columns

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID | `HasUuids` — no sequential IDs exposed; offline-sync safe |
| `structure_id` | FK → structures | Tenant anchor |
| `first_name`, `last_name` | string | |
| `date_of_birth` | date | nullable |
| `gender` | enum | male / female / other / not_specified |
| `address`, `postal_code`, `city` | string | nullable |
| `gir` | integer 1–6 | AGGIR autonomy scale; nullable |
| `medical_notes` | text | **ENCRYPTED** (RGPD Art 9) |
| `allergies` | text | **ENCRYPTED** |
| `medical_history` | text | **ENCRYPTED** |
| `current_treatments` | text | **ENCRYPTED** |
| `status` | enum | active / exited / deceased / erased |
| `is_erased` | boolean | RGPD Art 17 anonymisation flag |
| `deleted_at` | datetime | SoftDeletes |

### Encrypted Casts

```php
protected function casts(): array {
    return [
        'medical_notes'      => 'encrypted',
        'allergies'          => 'encrypted',
        'medical_history'    => 'encrypted',
        'current_treatments' => 'encrypted',
    ];
}
```

Values are AES-256-CBC encrypted using `APP_KEY`. They are **never** included in `BeneficiaryResource` (public-facing API shape). They only appear in `BeneficiaryDossierResource`, which is gated behind the `log_sensitive_read` middleware (every access is audit-logged).

### Relationships

```
Beneficiary
  ├── BelongsTo    Structure
  ├── HasMany      CarePlan            (all plans)
  ├── HasOne       CarePlan (active)   (where status = 'active')
  ├── HasMany      IntervenantAssignment
  ├── BelongsToMany User (active)     (via IntervenantAssignment, wherePivotNull)
  └── BelongsToMany User (all)        (full history)
```

### Access Control

| Role | Access |
|------|--------|
| coordinateur | All beneficiaries in own structure |
| dirigeant | All beneficiaries in own structure |
| referent_qualite | All (read-only) |
| rh | Basic profile only (no health data) |
| **intervenant** | **ONLY beneficiaries they are actively assigned to** |

The intervenant restriction is enforced in `BeneficiaryPolicy::view()`:

```php
if ($user->hasRole('intervenant')) {
    return IntervenantAssignment::active()
        ->where('user_id', $user->id)
        ->where('beneficiary_id', $model->id)
        ->exists();
}
```

### RGPD Erasure (anonymize)

`BeneficiaryService::anonymize()` replaces personal fields with anonymised values, sets `is_erased = true` and `status = 'erased'`. The record is **never deleted** — the legal audit trail must survive. This cannot be undone.

---

## 6. Model Design — CarePlan

**File:** `app/Models/CarePlan.php`  
**Table:** `care_plans`

### Purpose

The *plan d'accompagnement* (individualized care plan) is the legal document that governs how a beneficiary's care is delivered. Per CDC §4, every beneficiary must have a signed care plan. A beneficiary has **at most one active plan** at any time.

### Key Columns

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID | |
| `structure_id` | FK → structures | Tenant anchor |
| `beneficiary_id` | FK → beneficiaries | |
| `title` | string | |
| `objectives` | text | **ENCRYPTED** — may contain medical prognosis |
| `start_date`, `end_date` | date | nullable |
| `status` | enum | **draft → active → archived** |
| `archived_at` | datetime | Set on archive |
| `archived_reason` | text | Required on archive (HAS §4.3) |
| `created_by` | FK → users | nullable |

### Status Lifecycle

```
draft ──── activate ────► active ──── archive ────► archived
  │                                                    │
  └───────────────── soft-delete ─────────────────────┘
                     (dirigeant only)
```

`CarePlanService::activate()` runs in a `DB::transaction` and atomically archives any currently active plan for the same beneficiary before activating the new one. Two plans can never be active simultaneously.

### The Archive Freeze

Once archived, a care plan is **immutable**. Enforced at two layers:

1. **Policy** — `CarePlanPolicy::update()` returns `false` → 403
2. **Policy** — `PlannedTaskPolicy::create/update/delete` returns `false` → 403
3. **Service** — `PlannedTaskService::guardArchived()` throws `HttpException(409)`

This satisfies the requirement that archived plans serve as permanent historical records of care delivered during a past period.

---

## 7. Model Design — PlannedTask

**File:** `app/Models/PlannedTask.php`  
**Table:** `planned_tasks`

### Purpose

One line item within a care plan: a specific care activity to perform on each visit (e.g. "Morning wash — 30 min, daily, mandatory"). The complete set of tasks forms the checklist field intervenants follow.

### Key Columns

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID | |
| `structure_id` | FK → structures | **Auto-mirrored from parent CarePlan** |
| `care_plan_id` | FK → care_plans | |
| `title` | string max:200 | |
| `frequency` | enum | daily / weekly / monthly / on_demand / custom |
| `duration_minutes` | integer | nullable; 1–600 |
| `task_order` | integer | Display sequence within the plan |
| `mandatory` | boolean | default true |

### structure_id Auto-Mirroring

Tasks are never created independently — they always belong to a `CarePlan`. The `booted()` listener overwrites `structure_id` unconditionally:

```php
static::creating(function (PlannedTask $task): void {
    if ($task->care_plan_id) {
        $plan = CarePlan::find($task->care_plan_id);
        if ($plan) {
            $task->structure_id = $plan->structure_id;
        }
    }
});
```

This means passing a different `structure_id` in your payload has no effect — the tenant identity always comes from the parent plan.

### Auto Task Order

`PlannedTaskService::create()` calculates order automatically:

```php
$data['task_order'] ??= ($plan->tasks()->max('task_order') ?? -1) + 1;
```

- First task in a new plan → `task_order = 0`
- Each subsequent task → `max + 1`
- Pass an explicit `task_order` to insert at a specific position

### Reparenting Prevention

`PlannedTaskService::update()` strips two fields before saving:

```php
unset($data['care_plan_id'], $data['structure_id']);
```

A task cannot be moved to a different care plan or a different structure through the update endpoint. A future `moveTo()` service method would handle legitimate cross-plan moves with explicit validation.

### TaskFrequency Enum

| Value | French label |
|-------|-------------|
| `Daily` | Quotidien |
| `Weekly` | Hebdomadaire |
| `Monthly` | Mensuel |
| `OnDemand` | À la demande |
| `Custom` | Personnalisé |

---

## 8. Model Design — IntervenantAssignment

**File:** `app/Models/IntervenantAssignment.php`  
**Table:** `intervenant_assignments`

### Purpose

The assignment pivot formally records which intervenants serve which beneficiaries. This is a **time-stamped, audited, legally significant record** — not merely a many-to-many join.

Per CDC §3 (confidentialité) and RGPD minimal-access principle: an intervenant may only access data of beneficiaries they are **formally and actively assigned to**. Without an active assignment row, an intervenant cannot see the beneficiary in their list, access their profile, view the care plan, or log an intervention.

### Key Columns

| Column | Type | Notes |
|--------|------|-------|
| `id` | UUID | |
| `structure_id` | FK → structures | Tenant anchor |
| `user_id` | FK → users | The intervenant |
| `beneficiary_id` | FK → beneficiaries | |
| `assigned_by_user_id` | FK → users | nullable — who performed the assignment |
| `assigned_at` | datetime | When the assignment was opened |
| `unassigned_at` | datetime | **nullable** — when it was closed |
| `notes` | text | nullable — reason, instructions |

> **No `deleted_at` column** — history must never be destroyed. Closing an assignment sets `unassigned_at`; the row remains permanently visible.

### Partial Unique Index — No Duplicate Active Assignments

```sql
CREATE UNIQUE INDEX ON intervenant_assignments (user_id, beneficiary_id)
WHERE (unassigned_at IS NULL);
```

- Same intervenant cannot be assigned to the same beneficiary twice while both are active
- After closing (setting `unassigned_at`), a new assignment for the same pair is allowed
- The service also checks at the application layer (409 before the DB constraint fires)

### Scopes

```php
active()   → where('unassigned_at', null)
inactive() → whereNotNull('unassigned_at')
```

Used in: `BeneficiaryPolicy::view()`, `BeneficiaryController::show()`, `User::assignedBeneficiaries()`

### Assignment Workflow

```
Coordinateur opens beneficiary profile
  └── GET /beneficiaries/{id}
       └── Controller loads eligible intervenants (same structure, type=intervenant,
           not already actively assigned to this beneficiary)

Coordinateur submits form
  └── POST /beneficiaries/{id}/assignments
       ├── AttachIntervenantRequest validates intervenant_id
       ├── authorize() checks BeneficiaryPolicy::update
       └── IntervenantAssignmentService::assign()
            ├── 422 if cross-structure
            ├── 409 if already actively assigned
            └── Creates row with assigned_at = now()

Coordinateur ends assignment
  └── DELETE /assignments/{id}
       ├── IntervenantAssignmentPolicy::delete
       └── IntervenantAssignmentService::unassign()
            ├── 409 if already closed
            └── Sets unassigned_at = now()
            └── Intervenant immediately loses access to beneficiary data
```

---

## 9. Testing Strategy

**Framework:** Pest (NOT PHPUnit — overrides Laravel Boost default)  
**Rule:** Every feature ships with both a feature test AND a unit test.

**Current count:** 139 tests · 485 assertions · all green

### Feature Tests (`tests/Feature/`)

| File | Tests |
|------|-------|
| `Domain/Beneficiaries/BeneficiaryHttpTest.php` | HTTP happy paths, validation, role denials |
| `Domain/Beneficiaries/BeneficiaryPolicyTest.php` | Policy rules |
| `Domain/Beneficiaries/BeneficiaryTenantIsolationTest.php` | Cross-tenant leak (mandatory) |
| `Domain/CarePlans/CarePlanHttpTest.php` | HTTP endpoints |
| `Domain/CarePlans/CarePlanPolicyTest.php` | Policy rules |
| `Domain/CarePlans/CarePlanTenantIsolationTest.php` | Cross-tenant leak |
| `Domain/PlannedTasks/PlannedTaskHttpTest.php` | 11 tests: store/update/delete happy + denied + 404 + archive freeze |
| `Domain/Assignments/AssignmentHttpTest.php` | 12 tests |
| `Domain/Assignments/BeneficiaryPolicyAssignmentTest.php` | Assignment policy |
| `Domain/Assignments/IntervenantAssignmentTenantIsolationTest.php` | Cross-tenant leak |
| `Domain/Assignments/IntervenantRelationshipsTest.php` | Relationship queries |
| `Auditing/TenantAwareAuditTest.php` | Audit entries stamped with structure_id |
| `Rbac/RoleSeederTest.php` | All permissions seeded |
| `Rbac/TenantScopedRolesTest.php` | Roles scoped per tenant |
| `Tenancy/StructureScopeTest.php` | Global scope enforcement |
| `Middleware/SecurityHeadersTest.php` | HSTS, CSP, etc. present |
| `RateLimiting/LoginRateLimitTest.php` | Rate limits fire correctly |
| `HealthCheckTest.php` | /health/live + /health/ready |

### Unit Tests (`tests/Unit/Services/`)

| File | Coverage |
|------|----------|
| `BeneficiaryServiceTest.php` | create, update, anonymize |
| `CarePlanServiceTest.php` | create, update, activate, archive, copyFromTemplate |
| `IntervenantAssignmentServiceTest.php` | assign, unassign, conflict detection |
| `PlannedTaskServiceTest.php` | 8 tests: auto-order, explicit order, update, reparenting prevention, soft-delete, archive freeze (3 mutations) |

---

## 10. Security Architecture

Every write operation flows through this chain — no shortcuts:

```
Request
  │
  ├─ 1. Tenant scope  (BelongsToStructure global scope)
  │      Foreign rows are invisible → 404, not 403
  │
  ├─ 2. Policy        (BasePolicy::before checks ownership; named methods check permission)
  │      Insufficient role/permission → 403
  │
  ├─ 3. Form Request  (authorize() + validation rules)
  │      Invalid input → 422 with error bag
  │
  ├─ 4. Service       (DB::transaction + business invariants)
  │      Business rule violations → HttpException(409/422)
  │
  └─ 5. Audit log     (Auditable trait — automatic on every create/update/delete)
         Logged: user_id, structure_id, old_values, new_values, IP, user agent
```

### HDS Compliance Checklist

| Item | Status |
|------|--------|
| Encrypted health data at rest | ✅ `'encrypted'` cast on all medical fields |
| Audit trail on all health data reads/writes | ✅ Auditable + log_sensitive_read |
| MFA (TOTP via Fortify) | ✅ Mandatory for coordinateurs+ |
| Rate limiting on auth endpoints | ✅ 5/min login, 5/min two-factor |
| Security headers (HSTS, CSP, X-Frame-Options) | ✅ |
| Row-level multi-tenancy | ✅ Zero cross-tenant leakage (tested) |
| Soft deletes (legal retention) | ✅ All domain models |
| Partial unique index (no duplicate active assignments) | ✅ PostgreSQL constraint |
| S3 SSE-KMS encryption | ⏳ Configured in env; not yet E2E tested |
| PostgreSQL audit extension | ⏳ Phase 2 |
| Data retention policy enforcement | ⏳ Phase 2 |

---

## 11. Manual API Testing Guide

Since the mobile REST API (`/api/v1/*`) is not yet built, all manual testing is via browser with a seeded database.

### Setup

```bash
# Reset database and seed
php artisan migrate:fresh --seed

# Start dev server
composer run dev   # or: php artisan serve + npm run dev in separate terminals
```

### Seed Creates

After `php artisan db:seed`:
- 1 Structure: *Structure Test*
- 1 coordinateur user: `coordinateur@test.fr` / `password`
- 1 intervenant user: `intervenant@test.fr` / `password`
- 2–3 sample beneficiaries (check `database/seeders/`)

### Key Test Scenarios

#### Beneficiary show + dossier

```
1. Log in as coordinateur@test.fr
2. GET /beneficiaries  → should list all beneficiaries
3. Click any beneficiary → GET /beneficiaries/{id} → read-only profile (no medical fields)
4. Click "Dossier médical" → GET /beneficiaries/{id}/dossier → medical fields visible, access logged
5. Log in as intervenant@test.fr
6. GET /beneficiaries → should list ONLY assigned beneficiaries (empty if none assigned)
```

#### Care plan lifecycle

```
1. Log in as coordinateur
2. GET /beneficiaries/{id}/care-plans  → list plans
3. Create a plan → POST (form) → should land in draft status
4. Open plan → POST /care-plans/{id}/activate → status changes to active
5. POST /care-plans/{id}/archive (with reason) → status changes to archived
6. Try PUT /care-plans/{id} on the archived plan → expect 403
```

#### Planned task — archive freeze

```
1. Ensure you have an archived plan (from above)
2. Try POST /care-plans/{id}/tasks → expect 403
3. Try PUT /tasks/{any-task-id} on a task in the archived plan → expect 403
4. On a DRAFT plan, POST /care-plans/{id}/tasks with valid payload → expect redirect + task appears
```

#### Intervenant assignment

```
1. Log in as coordinateur
2. GET /beneficiaries/{id} → see "Équipe d'intervention" section
3. POST /beneficiaries/{id}/assignments with intervenant_id → intervenant assigned
4. Log in as intervenant → beneficiary now appears in their list
5. Log in as coordinateur → DELETE /assignments/{id} → unassigned
6. Log in as intervenant → beneficiary no longer appears
```

#### Cross-tenant isolation test

```
1. Create two structures with different coordinateur accounts
2. Log in as coordinateur A
3. Note a beneficiary UUID from structure B (if you can get it)
4. GET /beneficiaries/{structure-B-uuid} → expect 404 (not 403)
```

### Testing via cURL (session cookie required)

```bash
# Login first — get session cookie
curl -c cookies.txt -X POST https://your-app/login \
  -d "email=coordinateur@test.fr&password=password&_token=..."

# Then hit any endpoint with the cookie
curl -b cookies.txt https://your-app/beneficiaries
```

---

## 12. What's Next — Phase 1 Month 2+

### Month 2 — Interventions (M-TRACE)

The central tracking table. An intervention is a visit record: who visited, when, what was done.

**Planned columns:** `intervenant_id`, `beneficiary_id`, `care_plan_id`, `planned_date`, `actual_start/end`, GPS checkin coordinates, `status` (planned / in_progress / completed / cancelled / missed), `visit_mode` (mobile / web), `rapport_texte` (encrypted)

This module ties the care plan to actual delivery. A `PlannedTask` on the care plan maps to a task execution on the intervention record.

### Month 2 — Incidents / Événements Indésirables (M-INC)

Adverse events during care delivery. Per HAS §4.2, all incidents must be recorded within 24h and reviewed within 72h.

**Planned:** Severity enum (minor / moderate / serious / critical) · categorisation · status workflow (declared → under_review → resolved / escalated) · mandatory escalation to ARS for serious events · linked to beneficiary + intervenant + care plan

The existing `IncidentController` is a stub and will be fully replaced.

### Month 3 — QVCT Baromètre

Anonymous working-conditions survey for intervenants. Min 2 per year (CDC §6.1). Responses anonymised at storage time — the link between respondent and response is never stored.

### Month 3 — Plans d'Amélioration Continue (PAC)

Quality improvement plans generated from audit results and incident analysis. Each action has an owner, deadline, and status. Links to HAS referential quality criteria.

### Month 4 — Mobile REST API (`/api/v1/*`)

Sanctum personal access tokens · same Services as web controllers · offline SQLite sync · GPS checkin/checkout · binary upload for incident photos · conflict resolution strategy

### Phase 2 (Months 5–8) — Pro offering

Complete HAS audit module (~150 criteria) · Formation & habilitation tracking · Document management (signed care plan PDFs) · Advanced reporting dashboard · Automated notifications

### Phase 3 (Months 9–14) — Premium / AI

AI-assisted care plan suggestions · Predictive scheduling optimisation · Voice-to-structured-form incident reporting · Longitudinal QVCT trend analysis
