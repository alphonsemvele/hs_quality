# Phase 2 cross-cutting audit — 2026-05-04

## Concern 1 — Architecture
**Status: GREEN**

- Domain modeling is sound: Phase 2 modules (M3 QVCT, M4 Communication, M5 Competencies, M6 Audits+PAC) follow consistent trait-based patterns (BelongsToStructure, Auditable) with clear service boundaries separating orchestration from models.
- Service-vs-controller boundaries respected: QvctService, TrainingPlanService, AuditScoringService, PacGenerationService all encapsulate business logic; controllers delegate to services and never replicate logic.
- No DRY violations in Phase 2 code: QaService uses DB::table() for vote idempotency (justified by unique constraint requirement), WeakSignalDetector is focused pure-function, audit/PAC generation each have dedicated services.
- Abstraction layers clean: BasePolicy, BaseFormRequest, BelongsToStructure trait all provide reusable defense-in-depth without leaky abstractions.

## Concern 2 — Security
**Status: YELLOW**

- Form Request `authorize()` always implemented: all Communication, Competencies, Audits, Qvct Form Requests override authorize() with Policy checks (SendMessageRequest:12, RecordHabilitationRequest:12, RecordAuditResponseRequest:12). BaseFormRequest defaults to false for extra safety.
- Policy gates present on all domain models: 34 policies registered in AppServiceProvider:94-128, BasePolicy::before() tenant-checks before method-specific logic. However, **6 domain models lack policies**: AuditGridItem, DiscussionGroupMember, IncidentActionCorrective, IncidentSuivi, InterventionCompletedTask, InterventionPhoto. These are sub-entities (not top-level aggregates) but represent a minor gap.
- No `$guarded = []` found; all models use explicit `$fillable` (Message:30, QvctIndicator:22, Certification:25).
- No `{!! !!}` unsafe HTML found in Blade; Message model encrypts body (Message.php:42, use Auditable with `body` excluded from audit to prevent decryption forensics).
- No env() at runtime: grep confirms zero env() calls outside config files (CLAUDE.md rule 6 enforced).
- Raw DB:: calls in QaService (lines 73–91) are intentional: vote idempotency requires DB::table() to enforce unique constraint atomically inside transaction (justified in comment line 20-24).
- No obvious injection vectors: all user input validated through Form Requests, Model casts (encrypted, array), Policy checks.

## Concern 3 — Database
**Status: GREEN**

- Proper indexes on tenant columns: messages migration (line 51) has index(['structure_id', 'discussion_group_id', 'created_at']), audit_runs (line 49-50) has structure_id + status and structure_id + run_date for history queries.
- Unique constraints present where invariants demand: qa_answer_votes uniqueness on (qa_answer_id, user_id) ensures vote toggle idempotency; training_attendances has unique-(session, user) to prevent double-registration.
- Migrations idempotent: foreign keys use cascadeOnDelete or nullOnDelete with explicit constraints. QvctCampaign::fresh() is used after create to reload (QvctService:56), not raw data.
- Casts set on all relevant columns: QvctIndicator has decimal:2 for rates (line 40-43), Message has encrypted body + array attachments (Message:42-44), Certification has datetime casts (Certification:38-46), AuditRun has decimal/datetime (AuditRun:38+).
- No N+1 in service queries: AuditScoringService calls $run->grid->items()->sum() and $run->responses()->sum() (lines 32, 38) — relationships are eager-loaded at call site or are single aggregations; DashboardStatsService uses counts (not loops), queries cached 5 min (DashboardStatsService:25-39); recentIncidents eager-loads declarant:id,first_name,last_name (line 183).

## Concern 4 — Tenancy
**Status: GREEN**

- Every Phase 2 domain model uses BelongsToStructure: QvctIndicator, Message, TrainingPlan, AuditRun, Certification, Pac, Habilitation, TrainingSession, TrainingAttendance, DiscussionGroup, Document, QaQuestion, QaAnswer, and all Qvct/* models confirmed in grep.
- BasePolicy::before() guard enforced: BasePolicy (lines 29-44) checks model->getAttribute('structure_id') against $user->structure_id before method execution, returning false on mismatch.
- Cross-tenant Pest tests comprehensive: QvctTenantIsolationTest (12 test cases), MessageTenantIsolationTest, CompetenciesTenantIsolationTest, PacTenantIsolationTest, AuditGridTenantIsolationTest, all verify zero leaks on find($foreign_id) and count isolation per tenant.
- Global scope applied: BelongsToStructure trait enforces current_structure() binding on all queries (implemented via trait, verified in tests).

## Concern 5 — API design
**Status: GREEN**

- Consistent `/api/v1/{resource}/{action}` shape: QvctCampaignController->index, show, submitResponse follow RESTful patterns; SyncController exists for mobile batch operations.
- Idempotent middleware on writes: rate limiters configured in AppServiceProvider (lines 213-277) apply to login (5/min), two-factor (5/min), sync (configurable), mobile-api (configurable). All POST/PATCH routes inherit throttle:api or specific limiter.
- OpenAPI annotations sane: Scramble configured in AppServiceProvider::configureScramble() (lines 183-204), documents api/*, interventions, incidents, beneficiaries, care-plans, assignments, tasks; SecurityScheme set to http('bearer') for Sanctum tokens.
- Versioning preserved: API routes under `/api/v1/*` (QvctCampaignController in V1 namespace), no v0 or unversioned API endpoints found.

## Concern 6 — Performance
**Status: GREEN**

- Eager loading on lists: QvctCampaignController->index (line 28) uses with(['questionnaire:id,title,questions']), DashboardStatsService->recentIncidents (line 183) with(['declarant:id,first_name,last_name']), PacGenerationService->load relationships on pac->actions().
- No N+1 detected: all service query patterns use counts(), sums(), or single eager loads. DashboardStatsService caches aggregates 5 min, tagged by structure to invalidate coherently.
- Queue offload of >200ms work: QvctService::closeCampaign (line 109-111) dispatches NotifyReferentRhJob afterCommit() for each weak signal; CertificationExpiryAlertJob runs daily at 03:00 with idempotent windowing (CertificationExpiryAlertJob.php); PlannedTask sweep every 15 min with withoutOverlapping (routes/console.php:23-28).
- Dashboard counts cached: DashboardStatsService uses Cache::tags() with 5-min TTL (line 29), tagged per structure (line 29) so invalidation works on structure changes.

## Concern 7 — Reliability
**Status: GREEN** *(corrected from YELLOW after verification)*

- DB::transaction used on multi-write ops: QvctService::launchCampaign (line 44), QvctService::closeCampaign (line 95), AuditExecutionService::recordResponse (line 58), AuditExecutionService::finalise (line 87), PacGenerationService::generateForRun (line 47), TrainingPlanService operations. Good coverage.
- After-commit dispatch present: QvctService::closeCampaign (line 110) uses NotifyReferentRhJob::dispatch()->afterCommit() so notification doesn't fire if transaction rolls back.
- Idempotent jobs: CertificationExpiryAlertJob advances last_alert_window monotonically, no re-fire within same window; WeakSignalDetector deletes un-acknowledged signals before re-emitting, preserving operator decisions; TrainingPlanService::register re-activates cancelled attendance row rather than creating duplicate.
- Queue retries set on **all** notification jobs: CertificationExpiryAlertJob ($tries = 3), NotifyReferentRhJob ($tries = 3, line 29), NotifyARSJob ($tries = 3, line 17), NotifyResponsableSecteurJob ($tries = 3, line 14). The earlier "missing $tries" claim was an audit error — verified by direct grep.

## Concern 8 — Observability
**Status: YELLOW**

- Log lines on critical state changes: CertificationExpiryAlertJob::processCert (line 82) logs certification ID, structure, user, window, expires_at on each alert; NotifyReferentRhJob (line 38) logs signal_id, structure_id, campaign_id, team_tag, signal_type, severity.
- Audit trails via owen-it/laravel-auditing: QvctIndicator, QvctCampaign, QvctResponse, QvctWeakSignal, QvctJournalEntry, QvctActionPlan, QvctActionPlanItem, QvctExchangeRequest, Message, AuditRun, Certification, Habilitation, Pac, PacAction all Auditable (confirmed grep). QvctResponse and Message exclude body from auditInclude for encryption/anonymity (QvctResponse comment, Message:52-57).
- **Gap**: No explicit audit logging on PAC action state changes (status, responsible). PacAction has Auditable but PacAction-specific observers are registered (AppServiceProvider:179-180, PacObserver, PacActionObserver) — audit trail exists via observers but log events missing.

## Concern 9 — Background processing
**Status: GREEN**

- Cron entries with withoutOverlapping/onOneServer: interventions:sweep-missed (routes/console.php:25-26), qvct:indicators:snapshot (36-37), certifications:expiry-sweep (49-50) all use withoutOverlapping() and onOneServer(), plus runInBackground() so scheduler doesn't block.
- Jobs are tenant-context-preserving: CertificationExpiryAlertJob iterates all structures (line 55-57) and sets PermissionRegistrar team scope per cert structure (line 107) so role lookups work; NotifyReferentRhJob carries signal with structure_id (constructor param).
- Scheduled task entry validation: every Phase 2 background task (certification alerts, QVCT indicators, intervention sweep, weak signal notifications) has corresponding routes/console.php entry or Job dispatch.

## Concern 10 — Testing
**Status: GREEN**

- Pest only: grep confirms zero PHPUnit assertions; project rule enforced via CLAUDE.md.
- Feature + unit per slice: TrainingPlanServiceTest (unit, 50+ lines), MessageServiceTest, QvctServiceTest, AuditScoringServiceTest all present. Feature tests for policies (CompetenciesPoliciesTest, CommunicationPoliciesTest).
- Cross-tenant test per domain model: QvctTenantIsolationTest (12 cases), MessageTenantIsolationTest, CompetenciesTenantIsolationTest (covers training, certifications, habilitations), PacTenantIsolationTest, AuditExecutionTenantIsolationTest all verify zero leaks and proper isolation.
- Factories with semantic states: TrainingPlan factory supports ->published(), ->archived() states (used in TrainingPlanServiceTest:47); Structure/User factories have ->forStructure() semantic (TrainingPlanServiceTest:21-24).

## Concern 11 — Deployment
**Status: GREEN**

- config() not env() at runtime: zero env() calls in app/ (CLAUDE.md rule 6 enforced, verified via grep).
- AppServiceProvider::guardProductionDebug present: lines 153-162, throws RuntimeException if APP_ENV=production && APP_DEBUG=true.
- Secrets not committed: no .env file in codebase (verified), Sanctum APP_KEY, database passwords come from deployment platform secrets.

## Concern 12 — Compliance
**Status: GREEN**

- Auditable on health-data models: Message, QvctIndicator, QvctCampaign, QvctJournalEntry, QvctActionPlan, QvctActionPlanItem, AuditRun, Certification, Habilitation, Pac, PacAction all Auditable. Encrypted columns (body, report_text) excluded from audit logs (Message:52-57).
- RGPD compliance notes: anonymity invariant preserved in QvctResponse (no user_id column, line 109); audit trails exclude sensitive bodies post-encryption; soft-delete on Message preserves moderation trail (Message migration, line 49).
- French data residency: S3 configured for HDS-hosted (AWS Paris or OVHcloud France) per CLAUDE.md; database on PostgreSQL 16 (on-premises or compliant hosting).
- No PII in logs: CertificationExpiryAlertJob logs IDs only (cert->id, user->id, not names); NotifyReferentRhJob logs signal metadata, no personal data; DashboardStatsService never logs individual rows.

---

## Verified gaps to close before commercial release

After spot-checking the agent's findings against the actual codebase, the verified gap list is shorter than the original draft. Two of the agent's claims were inaccurate (`$tries` missing — false; FK indexes missing — false, all four cited tables already have composite `(structure_id, fk, …)` indexes which Postgres uses for tenant-filtered WHERE clauses).

The real, verified gaps in priority order:

1. ~~**NotifyReferentRhJob mail wiring incomplete**~~ — **CLOSED 2026-05-05** via `App\Notifications\WeakSignalDetectedNotification` + updated `NotifyReferentRhJob::handle()`. Recipients: rh + dirigeant + referent_qualite in the signal's structure. Body explicitly reaffirms QVCT anonymity (no respondent identity surfaces). 5 new tests in `tests/Feature/Notifications/WeakSignalDetectedNotificationTest.php` covering recipient roster, cross-tenant no-leak, anonymity invariant in body, severity-shaped urgency line, null team_tag.

2. ~~**Sub-entity policies missing**~~ — **DOWNGRADED 2026-05-05 to NOT-A-GAP** after route-binding inspection. The route-binding audit in `routes/api.php` and `routes/web.php` finds only one sub-entity with a direct binding: `InterventionPhoto` (via `DELETE interventions/{intervention}/photos/{photo}`). Three independent defenses already cover it: (a) `BelongsToStructure` global scope on the model filters the route binding to current tenant, (b) the controller authorizes the parent ability `$this->authorize('update', $intervention)`, (c) the controller cross-checks `$photo->intervention_id !== $intervention->id` and aborts 404. The other five sub-entities (AuditGridItem, DiscussionGroupMember, IncidentActionCorrective, IncidentSuivi, InterventionCompletedTask) have **no direct route bindings at all** — they are reachable only through their parent aggregate's policy. Adding 6 policy classes here would add ~180 lines of boilerplate for zero detection signal and zero closed exploit path. Decision: don't write them. If a future slice adds a direct route binding to one of these sub-entities, write the policy at that time, not before.

3. ~~**PAC state-change application logging**~~ — **CLOSED 2026-05-05** with a thin layer: `PacObserver` and `PacActionObserver` now `Log::info` on (a) `wasChanged('status')` transitions and (b) deletion. Save events that don't touch status do NOT log (avoids noise). The `audits` table remains the formal compliance record; the log lines are an ergonomic ops layer for downstream alerting (Datadog, CloudWatch). Enum-to-primitive coercion via `instanceof \BackedEnum` so log consumers receive strings, not opaque PHP objects. 5 new tests in `tests/Unit/Observers/PacObserversLoggingTest.php` covering positive transitions, no-log on non-status updates, and delete logging. The two retention systems are intentionally separate: audits = forever / compliance, logs = ~90 days / operations.

(The two original Top-5 entries that referenced missing `$tries` and missing FK indexes have been removed — both were audit-agent errors. Verified by direct grep against the codebase.)
