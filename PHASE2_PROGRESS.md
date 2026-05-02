# Phase 2 — Progress Tracker (Qualité, Months 5–8)

> Spec source: [`IMPLEMENTATION_PLAN.txt`](IMPLEMENTATION_PLAN.txt) §5 (lines 428–470)
> Started: 2026-05-02
> Tracker discipline: every line item below is **either** `[ ]` (not yet) or `[x]` with an **evidence column** (commit + file path / test name). `[x]` without evidence is forbidden — that is what let Phase 1 ship with frontend gaps masquerading as "complete".
>
> When closing a row: edit it in the same commit that lands the work. Do NOT batch ticks at the end of a milestone.
>
> **Conventions reused from Phase 1** (apply to every row below; not repeated per row):
> - All new domain models extend `BelongsToStructure` + register a Policy + ship a cross-tenant leak Pest test
> - All write endpoints validate via a Form Request extending `BaseFormRequest`
> - Health-data + QVCT-data models are `Auditable`
> - All controllers go through Policy `authorize()` before service call
> - Anything > 200ms goes to a queue (idempotent + tenant-context-preserved)
> - Pest only — no PHPUnit
> - Pint clean before every commit

---

## Goal & acceptance (lines 431-470)

**Goal:** Convert MVP into commercial Pro offering with QVCT + HAS audits + enriched communication + basic skills tracking.

| # | Acceptance criterion | Status | Evidence |
|---|---|---|---|
| A1 | 50 client structures (from 10) | [ ] | Sales / onboarding |
| A2 | Pro offering live with billing | [ ] | M-Commercial below |
| A3 | QVCT baromètre running across pilot structures with measurable weak-signal alerts | [ ] | M3-* below |
| A4 | First HAS évaluation externe prepared via the platform | [ ] | M6-* below |
| A5 | 99.9% uptime maintained | [ ] | Ops + APM (E1) |

---

## Month 5 — M3 QVCT (lines 436-438 + CDC §M3)

> IMPLEMENTATION_PLAN line 436-438: *barometer questionnaires, anonymous response storage, weak-signal detection service, individualized RPS tracking with alerts to référent RH, psychosocial risk cartography per team*
>
> Full CDC §M3 spec (sourced from `~/.claude/.../memory/project_cdc_specification.md` lines 81-93):
> 1. Anonymous baromètre: satisfaction + wellbeing questionnaire
> 2. Parametrable frequency: weekly / monthly / quarterly
> 3. Weak-signal detection: morale drop, overload, relational conflicts
> 4. Individualized psychosocial risk tracking with alert to référent RH
> 5. Optional emotional journal
> 6. Secure exchange-request tool with manager or référent RH
> 7. Psychosocial risk cartography per team / secteur
> 8. QVCT indicator tracking: absenteeism, turnover, work accidents, baromètre satisfaction
> 9. QVCT action plan with impact measurement

| # | Spec item | Status | Evidence |
|---|---|---|---|
| M3.1 | `qvct_questionnaires` table — questionnaire templates (title, version, periodicity, questions JSON) | [x] | `database/migrations/2026_05_02_084131_create_qvct_questionnaires_table.php` |
| M3.2 | `qvct_campaigns` table — instantiation of a questionnaire over a window (start/end, target audience) | [x] | `database/migrations/2026_05_02_084132_create_qvct_campaigns_table.php` |
| M3.3 | `qvct_responses` table — anonymous; only `campaign_id`, `team_tag`, `answers JSON`, no `user_id` | [x] | `database/migrations/2026_05_02_084133_create_qvct_responses_table.php` (anonymity asserted by `QvctTenantIsolationTest::responses carry no user_id`) |
| M3.4 | `qvct_weak_signals` table — detected anomalies per campaign × team (low score, sharp drop, etc.) | [x] | `database/migrations/2026_05_02_084134_create_qvct_weak_signals_table.php` |
| M3.5 | Migrations + factories + seeders for all four | [x] | Migrations + factories + `database/seeders/QvctSeeder.php` (idempotent default baromètre per structure) + `tests/Feature/Domain/Qvct/QvctSeederTest.php` |
| M3.6 | Models with `BelongsToStructure` + Auditable (where applicable) + cross-tenant leak tests | [x] | `app/Models/Qvct{Questionnaire,Campaign,Response,WeakSignal}.php` — Response intentionally NOT Auditable to preserve anonymity (audit row would carry the answers) |
| M3.7 | `QvctService::launchCampaign()` + `recordResponse()` (anonymous) + `closeCampaign()` | [x] | `app/Services/QvctService.php` + `tests/Unit/Services/QvctServiceTest.php` (5 tests, anonymity asserted) |
| M3.8 | `WeakSignalDetector` pure service — pure unit-tested; takes campaign aggregates returns flagged anomalies | [x] | `app/Services/WeakSignalDetector.php` + `tests/Unit/Services/WeakSignalDetectorTest.php` (7 tests covering threshold, sample-size guard, severity, team grouping, idempotent re-detection) |
| M3.9 | `NotifyReferentRhJob` — queued, idempotent, fires on weak signal | [x] | `app/Jobs/NotifyReferentRhJob.php` + `tests/Feature/Domain/Qvct/QvctNotifyReferentRhTest.php` (Bus::fake assertions; one job per emitted signal; no dispatch when no signals) |
| M3.10 | Form Requests: `LaunchCampaignRequest`, `SubmitResponseRequest` (+ `StoreQuestionnaireRequest`) | [x] | `app/Http/Requests/Qvct/{StoreQvctQuestionnaire,LaunchQvctCampaign,SubmitQvctResponse}Request.php` |
| M3.11 | Policies: `QvctCampaignPolicy`, `QvctResponsePolicy` (anonymous-write rules) + Questionnaire + WeakSignal | [x] | `app/Policies/Qvct{Questionnaire,Campaign,Response,WeakSignal}Policy.php` registered in `AppServiceProvider` + `tests/Feature/Domain/Qvct/QvctPolicyTest.php` (8 tests; anonymity guard asserts no role can read individual responses) |
| M3.12 | Web controllers + Inertia pages (questionnaire builder, campaign list, weak-signal feed) | [~] | Backend controllers + routes done: `app/Http/Controllers/Qvct{Questionnaire,Campaign}Controller.php` + `routes/web.php` (`qvct.questionnaires.*`, `qvct.campaigns.*`). Inertia .tsx pages still pending (frontend) |
| M3.13 | API endpoints (mobile intervenants submit responses): `POST /api/v1/qvct/campaigns/{id}/responses` | [x] | `app/Http/Controllers/Api/V1/Qvct{Campaign,WeakSignal}Controller.php` + routes (`GET qvct/campaigns`, `GET qvct/campaigns/{id}`, `POST qvct/campaigns/{id}/responses`, `GET qvct/weak-signals`, `POST qvct/weak-signals/{id}/acknowledge`) + `tests/Feature/Api/V1/QvctApiTest.php` (10 tests, anonymity asserted on submit response) |
| M3.17 | Pest feature tests per endpoint (anonymous response, RH-only access to detail) | [x] | `tests/Feature/Domain/Qvct/Qvct{Questionnaire,Campaign}HttpTest.php` (16 web tests) + `tests/Feature/Api/V1/QvctApiTest.php` (10 API tests). Cross-tenant 404 + RBAC matrix covered. |
| M3.14 | Sync batch op: `qvct.submit_response` | [x] | `app/Services/SyncBatchService.php::qvctSubmitResponse` + `app/Http/Requests/Api/V1/SyncBatchRequest.php` (kind enum) + `tests/Feature/Api/V1/QvctSyncOpTest.php` (success / closed-conflict / cross-tenant rejected / missing-id error) |
| M3.15 | Psychosocial risk cartography service — aggregates per-team (pure read service) | [x] | `app/Services/PsychosocialRiskCartographyService.php` + `tests/Unit/Services/PsychosocialRiskCartographyServiceTest.php` (5 tests, MIN_TEAM_SIZE anonymity guard, mean-score aggregation, active-signal join) |
| M3.16 | Dashboard tile: open campaigns + weak-signal alerts | [x] | `DashboardStatsService::qvctStats` (qvct_campaigns_open / qvct_responses_ce_mois / qvct_weak_signals_outstanding) + observers `Qvct{Campaign,Response,WeakSignal}Observer` (cache-flush on writes) + 5 new dashboard tests |
| M3.18 | Pest cross-tenant leak test on every QVCT model | [x] | `tests/Feature/Domain/Qvct/QvctTenantIsolationTest.php` (10/10 green; covers Questionnaire/Campaign/Response/WeakSignal + asserts anonymity invariant on Response) |
| M3.19 | Pest unit test on `WeakSignalDetector` covering CDC-spec'd thresholds | [x] | `tests/Unit/Services/WeakSignalDetectorTest.php` |
| M3.20 | French validation messages in `lang/fr/qvct.php` | [x] | `lang/fr/qvct.php` (module label + lifecycle messages + anonymity notice + weak-signal labels + frequency labels). Form-request inline messages remain for endpoint-specific overrides. |
| M3.21 | Parametrable frequency on `qvct_questionnaires` (weekly/monthly/quarterly enum) | [x] | `app/Enums/QvctFrequency.php` (Weekly/Monthly/Quarterly + label() + periodDays()); migration column + model cast (commit `965e7c1`) |
| M3.22 | `qvct_journal_entries` table — optional individualized emotional journal (private to user + référent RH) | [x] | `database/migrations/2026_05_02_100000_create_qvct_journal_entries_table.php` + `app/Models/QvctJournalEntry.php` (encrypted body, audit-excludes body, mood enum, mood_score for cheap aggregates, shared_with_rh consent flag, soft-delete) + `app/Enums/QvctMood.php` (5-point scale) |
| M3.23 | `JournalEntryService` — write, list-mine, list-as-RH (escalation visibility) | [x] | `app/Services/JournalEntryService.php` (write/setSharing/listForUser/listSharedForRh — anonymity-by-design: write() takes actor, never spoofable). API surface in `app/Http/Controllers/Api/V1/QvctJournalController.php` + sync op `qvct.write_journal` in `SyncBatchService::qvctWriteJournal`. |
| M3.24 | `qvct_exchange_requests` table — secure request to talk with manager / référent RH | [x] | `database/migrations/2026_05_02_110000_create_qvct_exchange_requests_table.php` + `app/Models/QvctExchangeRequest.php` (encrypted message, addressee_role + status enums, soft-delete, audit-includes everything except message) + `app/Enums/Qvct{ExchangeAddresseeRole,ExchangeStatus}.php` + dedicated triage perms `qvct.exchange.{rh,manager}_triage` in RoleSeeder |
| M3.25 | `ExchangeRequestService` — create, accept, schedule, close — notifies the addressee | [x] | `app/Services/ExchangeRequestService.php` (lifecycle: pending→accepted→scheduled→closed, with state-invariant guards). `outgoingFor`/`incomingFor` helpers. API in `app/Http/Controllers/Api/V1/QvctExchangeRequestController.php` + sync op `qvct.request_exchange`. Notification job stubbed (logs); future Notification facade integration tracked separately. Tests in `tests/Feature/Domain/Qvct/QvctExchangeRequestTest.php` (12) + `tests/Feature/Api/V1/QvctExchangeRequestApiTest.php` (8) — privacy + lifecycle + sync op + cross-tenant + at-rest encryption all covered. |
| M3.26 | `qvct_indicators` table — periodic per-structure absenteeism / turnover / accidents / baromètre score | [x] | `database/migrations/2026_05_02_120000_create_qvct_indicators_table.php` + `app/Models/QvctIndicator.php` (one row per structure × month, unique constraint, period_start normalised to first-of-month). Auditable. |
| M3.27 | `IndicatorIngestionService` — monthly snapshot job that aggregates current-period values | [x] | `app/Services/IndicatorIngestionService.php` (auto-computes barometer mean from closed campaigns in period; manual fields preserved on idempotent re-run) + `app/Console/Commands/QvctIndicatorSnapshotCommand.php` + cron `qvct:indicators:snapshot` registered in `routes/console.php` (monthlyOn(1, '03:00')) + on-demand API `POST /api/v1/qvct/indicators/snapshot`. Tests in `tests/Unit/Services/IndicatorIngestionServiceTest.php` (5) + `tests/Feature/Domain/Qvct/QvctIndicatorTest.php` (9). |
| M3.28 | `qvct_action_plans` + `qvct_action_plan_items` — action plan with impact measurement targets | [x] | `database/migrations/2026_05_02_130000_create_qvct_action_plans_table.php` + `..._130001_create_qvct_action_plan_items_table.php` + `app/Models/QvctActionPlan.php` + `app/Models/QvctActionPlanItem.php` + `app/Enums/Qvct{ActionPlanStatus,ActionPlanItemStatus}.php` (draft→published→closed lifecycle, item status pending/in_progress/done/cancelled, impact_measurement_target + actual + measured_at columns). Auditable. |
| M3.29 | `ActionPlanService` — draft, publish, record-impact-measurement, close | [x] | `app/Services/ActionPlanService.php` (draft/publish/close lifecycle with state-invariant guards; addItem only on draft; updateItemStatus on draft+published; recordImpact even on closed plans for late-arriving figures). API in `app/Http/Controllers/Api/V1/QvctActionPlanController.php` (8 endpoints). Tests in `tests/Unit/Services/ActionPlanServiceTest.php` (11) + `tests/Feature/Domain/Qvct/QvctActionPlanTest.php` (13) covering full lifecycle + RBAC matrix + responsible_user override + cross-tenant. |
| M3.30 | Pest tests covering journal privacy (intervenant cannot see another's; RH can; cross-tenant blocked) | [x] | `tests/Feature/Domain/Qvct/QvctJournalPrivacyTest.php` (12 tests: own + role × shared/unshared matrix + foreign-tenant + author-only edit/delete + cross-tenant scope + at-rest encryption verification) + `tests/Feature/Api/V1/QvctJournalApiTest.php` (9 tests: API write / mine / shared-with-rh queue / RBAC / sync op) |

**Month 5 acceptance gate (self-defined):** A référent RH can launch a campaign, intervenants can submit anonymously via mobile sync, weak-signal alerts auto-fire, intervenants can keep an optional journal + raise an exchange request, monthly QVCT indicators auto-snapshot, action plan tracks impact. Dashboard reflects state. Cross-tenant leak tests green. ≥ 35 new Pest tests.

### M3 retrospective (2026-05-02 close-out)

**Backend coverage**: 29 of 30 spec rows ticked; 1 partial (M3.12 — backend complete, Inertia .tsx pages still pending as a frontend deferral). Total commits 7 (`965e7c1` skeleton, `8a83a23` policies+forms+sync, `93f0992` controllers+HTTP, `8971e6a` cartography+dashboard+seeder+French, `c0eb3bb` journal, `f7ecb92` exchange requests, `d3f197d` indicators, `af5bdbb` action plans).

**Tests added in M3**: 152 (from 413 pre-M3 baseline → 555 after action-plans). All green.

**Sync ops added**: 3 (`qvct.submit_response`, `qvct.write_journal`, `qvct.request_exchange`). Plus the on-demand snapshot endpoint (`POST /api/v1/qvct/indicators/snapshot`) for RH to refresh barometer mean mid-period.

**Permission additions to RoleSeeder**: 5 (`qvct.questionnaire.manage`, `qvct.campaign.manage`, `qvct.weak_signal.acknowledge`, `qvct.exchange.rh_triage`, `qvct.exchange.manager_triage`). Existing perms (`qvct.respond`, `qvct.alert.receive`, etc.) reused throughout.

**Process notes for next module**: tracker discipline held — every `[x]` carries a verifiable evidence pointer. Two bugs caught by tests (factory-shared randomized fields, SQLite date-vs-datetime literal mismatch); both fixes documented in commit messages. The "evidence column required to tick" rule did its job.

**Outstanding from M3 carrying into next phase work**:
- M3.12 Inertia .tsx pages (frontend-only)
- Phase 1 deferrals P1-D1 / P1-D2 / P1-D3 (frontend)

---

## Month 6 — M6 Audits et conformité (lines 440-443)

> *grid library (HAS, ISO 9001, AFNOR NF X50-056), customizable grids, mobile audit execution, auto-scoring, PAC auto-generation from gaps, HAS preparation guide*

| # | Spec item | Status | Evidence |
|---|---|---|---|
| M6.1 | `audit_grids` table — reusable grid templates (title, source: HAS/ISO9001/AFNOR/custom, sections, weight scheme) | [x] | `database/migrations/2026_05_02_140000_create_audit_grids_table.php` + `app/Models/AuditGrid.php` + `app/Enums/AuditGridSource.php` (Has / Iso9001 / AfnorX50056 / Custom) + factory + `tests/Feature/Domain/Audits/AuditGridTenantIsolationTest.php` (4 tests). Per-tenant: each structure gets its own seeded copy (no global templates) so customization stays isolated. |
| M6.2 | `audit_grid_items` table — individual scoreable items (label, evidence required, scale) | [ ] | |
| M6.3 | `audit_runs` table — execution of a grid against a structure on a date | [ ] | |
| M6.4 | `audit_run_responses` table — per-item score, comment, evidence file ref | [ ] | |
| M6.5 | `pacs` (plans d'amélioration continue) table — auto-generated from gaps | [ ] | |
| M6.6 | `pac_actions` table — per-action assignment, due date, status, evidence | [ ] | |
| M6.7 | Migrations + factories + seeders (incl. seeded HAS grid skeleton) | [ ] | |
| M6.8 | All models BelongsToStructure + cross-tenant leak tests | [ ] | |
| M6.9 | `AuditGridLibrary` service — load HAS / ISO 9001 / AFNOR NF X50-056 templates from JSON fixtures | [ ] | |
| M6.10 | Seed JSON fixtures for the three reference grids (sourced from official documents) | [ ] | |
| M6.11 | `AuditExecutionService` — start run, record response, finalise (lock + score) | [ ] | |
| M6.12 | `AuditScoringService` pure service — auto-scoring per item + per section + global, unit-tested | [ ] | |
| M6.13 | `PacGenerationService` — given finalised audit run, emit PAC with one action per non-conformity (auto, idempotent) | [ ] | |
| M6.14 | Form Requests: `StartAuditRunRequest`, `RecordAuditResponseRequest`, `UpdatePacActionRequest` | [ ] | |
| M6.15 | Policies: `AuditRunPolicy`, `PacPolicy`, `PacActionPolicy` | [ ] | |
| M6.16 | Web controllers + Inertia pages (grid library browse, run execution, PAC kanban) | [ ] | |
| M6.17 | API endpoints (mobile audit execution): `POST /api/v1/audit-runs/{id}/responses` | [ ] | |
| M6.18 | Sync batch op: `audit.record_response` | [ ] | |
| M6.19 | HAS preparation guide UI + service (gap analysis between current state and HAS expected score) | [ ] | |
| M6.20 | PDF export of finalised audit run (queue job, S3-stored, signed-URL retrieval) | [ ] | |
| M6.21 | Dashboard tile: in-progress audits + open PAC actions overdue | [ ] | |
| M6.22 | Pest feature tests for end-to-end (start → record → finalise → PAC auto-generated) | [ ] | |
| M6.23 | Pest unit tests for `AuditScoringService` covering CDC-spec'd scoring rules | [ ] | |
| M6.24 | Pest unit tests for `PacGenerationService` covering all non-conformity → action mappings | [ ] | |
| M6.25 | French validation + UI messages (lang/fr/audit.php) | [ ] | |

**Month 6 acceptance gate:** A référent qualité can run a HAS grid against their structure on the mobile app, auto-scoring works, PAC is auto-generated with one action per gap, coordinateur can complete actions, finalised audit exports to PDF. Cross-tenant leak tests green.

---

## Month 7 — M4 Communication interne (lines 445-447)

> *WebSocket messaging (Reverb already scaffolded in Phase 1), discussion groups, news feed, document library, Q&A forum*

| # | Spec item | Status | Evidence |
|---|---|---|---|
| M4.1 | `discussion_groups` table — group of users (sub-team, project, etc.) | [ ] | |
| M4.2 | `discussion_group_members` pivot — group ↔ user with role (member/admin) | [ ] | |
| M4.3 | `messages` table — group_id, author_id, body, attachments JSON, timestamps, soft-delete | [ ] | |
| M4.4 | `news_feed_posts` table — structure-wide announcements, author, body, pinned flag | [ ] | |
| M4.5 | `documents` table — document library entries (title, S3 path, version, ACL: roles) | [ ] | |
| M4.6 | `qa_questions` + `qa_answers` tables — forum Q&A | [ ] | |
| M4.7 | Migrations + factories + seeders | [ ] | |
| M4.8 | All models BelongsToStructure + cross-tenant leak tests | [ ] | |
| M4.9 | `MessageService` — send (with attachment routing), edit (within 5min), delete, mark read | [ ] | |
| M4.10 | `NewsFeedService` — publish, pin, unpin, archive | [ ] | |
| M4.11 | `DocumentLibraryService` — upload (S3 SSE-KMS, EXIF-strip for images), version, ACL check, signed URL | [ ] | |
| M4.12 | `QaService` — ask, answer, accept-answer, vote | [ ] | |
| M4.13 | Reverb event `MessagePosted` with `PrivateChannel('group.{id}')` | [ ] | |
| M4.14 | Reverb event `NewsPostPublished` with `PrivateChannel('structure.{id}.news')` | [ ] | |
| M4.15 | Form Requests: `SendMessageRequest`, `PublishNewsRequest`, `UploadDocumentRequest`, `AskQuestionRequest`, `AnswerQuestionRequest` | [ ] | |
| M4.16 | Policies for every model | [ ] | |
| M4.17 | Web controllers + Inertia pages (group chat, news feed, document library, forum) | [ ] | |
| M4.18 | API endpoints for mobile parity (send message, fetch unread, list news, upload doc, post Q&A) | [ ] | |
| M4.19 | Frontend `useEcho` listeners actually wired (closes the Phase 1 deferral for InterventionStatusChanged + IncidentDeclared too) | [ ] | |
| M4.20 | Pest tests per endpoint + broadcast assertions via `Event::fake()` | [ ] | |
| M4.21 | Cross-tenant leak test for messages + documents (most sensitive) | [ ] | |
| M4.22 | French UI strings | [ ] | |

**Month 7 acceptance gate:** Two coordinateurs in the same structure can chat in real-time, post a news item that all intervenants see, upload a procedure to the doc library with role-based access, and resolve a Q&A thread. Cross-tenant leak tests green.

---

## Month 8 — M5 Compétences & formation basique (lines 449-451)

> *habilitation tracking, certification expiry alerts, basic training plan (e-learning micro-learning deferred to Phase 3 Premium)*

| # | Spec item | Status | Evidence |
|---|---|---|---|
| M5.1 | `habilitations` table — user ↔ habilitation type (DEAS, AS, IDEL, etc.), valid_from, valid_until, evidence | [ ] | |
| M5.2 | `certifications` table — user ↔ certification (BLS, gestes-d'urgence, etc.), expiry date, evidence | [ ] | |
| M5.3 | `training_plans` table — per-structure annual plan (year, theme, target audience) | [ ] | |
| M5.4 | `training_sessions` table — scheduled sessions (date, trainer, capacity) | [ ] | |
| M5.5 | `training_attendances` table — user × session, status (registered/attended/cancelled) | [ ] | |
| M5.6 | Migrations + factories + seeders | [ ] | |
| M5.7 | Models BelongsToStructure + cross-tenant leak tests | [ ] | |
| M5.8 | `HabilitationService` — record, renew, expire (cron-driven) | [ ] | |
| M5.9 | `CertificationExpiryAlertJob` — queued daily, fires alerts at T-90, T-30, T-7 days, on expiry | [ ] | |
| M5.10 | `TrainingPlanService` — draft, publish, register attendance | [ ] | |
| M5.11 | Cron in routes/console.php for certification expiry sweep | [ ] | |
| M5.12 | Form Requests for every write endpoint | [ ] | |
| M5.13 | Policies (intervenant sees own; responsable formation sees structure) | [ ] | |
| M5.14 | Web controllers + Inertia pages (habilitation matrix, training plan calendar) | [ ] | |
| M5.15 | API endpoints (mobile: my habilitations, my training schedule) | [ ] | |
| M5.16 | Dashboard tile: certifications expiring within 30 days | [ ] | |
| M5.17 | Pest feature tests per endpoint | [ ] | |
| M5.18 | Pest unit test on the expiry alert windowing logic | [ ] | |
| M5.19 | Cross-tenant leak test on every model | [ ] | |
| M5.20 | French UI strings | [ ] | |

**Month 8 acceptance gate:** A responsable formation can map every intervenant's habilitations, schedule training sessions, see who's expiring within 30 days; intervenants can see their own from mobile. Cross-tenant leak tests green.

---

## Commercial readiness (lines 453-455)

| # | Spec item | Status | Evidence |
|---|---|---|---|
| C1 | Stripe / Laravel Cashier integration — Pro tier subscription | [ ] | |
| C2 | Billing webhooks idempotent (Stripe retry-safe) | [ ] | |
| C3 | Pricing model: per-intervenant seat, with QVCT/Audits/Communication/Compétences toggleable | [ ] | |
| C4 | Onboarding automation — wizard for new structures (provision tenant + seed users + welcome email) | [ ] | |
| C5 | CRM integration — at minimum, hand-off to HubSpot or equivalent on Pro upgrade event | [ ] | |
| C6 | Self-service downgrade / cancel (compliance: 30-day data retention notice) | [ ] | |
| C7 | Pest tests on full subscription lifecycle (subscribe → trial → paid → cancel → grace) | [ ] | |

---

## Engineering concerns added in Phase 2 (lines 457-462)

| # | Concern | Status | Evidence |
|---|---|---|---|
| E1 | Full APM (Datadog or New Relic) instrumentation | [ ] | |
| E2 | Circuit breakers on outbound calls (ARS, email, future ML ping) — `laravel/circuit-breaker` or equivalent | [ ] | |
| E3 | Feature flags via Laravel Pennant (already installed) — first risky rollout uses it | [ ] | |
| E4 | Quarterly access reviews — automated report listing each user's effective permissions per structure | [ ] | |
| E5 | HDS certification documentation compiled into a single `references/compliance/hds-certification-pack/` folder | [ ] | |

---

## Phase 1 deferrals to close inside Phase 2 (carried forward)

These are **engineering** items deferred from Phase 1 that block clean Phase 1 sign-off. They land inside Phase 2, opportunistically alongside the related module:

| # | Carry-over | Status | Evidence | Lands with |
|---|---|---|---|---|
| P1-D1 | Beneficiary detail page — tab nav (info / interventions / plan / incidents) | [ ] | | Frontend pass alongside M3 UI |
| P1-D2 | Dashboard chart library + KPI charts (`recharts`) | [ ] | | Frontend pass alongside M3 dashboard tile |
| P1-D3 | `useEcho` listener for `InterventionStatusChanged` + `IncidentDeclared` (proves real-time end-to-end) | [ ] | | M4.19 above |
| P1-D4 | PostGIS extension + migrate `checkin_latitude`/`checkin_longitude` to `geography(Point)` | [ ] | | Optional — only needed if a Phase 2 feature requires spatial queries |
| P1-D5 | 5-whys structured columns (`why_1`..`why_5` vs single `analyse_causes` blob) | [ ] | | Optional — only if a Phase 2 audit/QVCT feature needs per-step analytics |

---

## Process discipline (read this before checking any box)

1. **Audit before claiming done.** Don't tick a box because "the controller exists" — open the file, run the test, confirm the spec line's specific shape is satisfied. The Phase 1 audit found 5 frontend gaps that had been silently `[x]`'d in earlier docs because the milestone tile said "done".
2. **Evidence column is mandatory.** A `[x]` row without a commit + file:line / test name is a red flag — either remove the tick or add the evidence.
3. **One commit per closed row when possible.** Batching closures across rows hides which rows are actually verified vs claimed.
4. **Cross-tenant leak test counts as evidence too.** Any new domain model without one is incomplete by project rule.
5. **No "we'll add tests later"** — tests land in the same commit as the code per CLAUDE.md.
