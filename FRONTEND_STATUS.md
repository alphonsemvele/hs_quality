# QualitéDomicile — Frontend Route Status & Manual Testing Guide

> Generated: 2026-05-18 · Base URL: `http://localhost:8000`
> Update this file whenever a route is wired or a page is created.

---

## Legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Fully wired — real DB queries, real service calls, page exists |
| ⚠️ | Partial scope — used in the data visibility matrix to indicate a user sees a filtered subset, not all records |
| 🔴 | Stub write — POST/PUT/DELETE returns `back()->with(...)` with no real work done |
| ❌ | Page missing — controller calls `Inertia::render(...)` for a `.tsx` file that does not exist (will 500) |
| 📄 | Static render — no backend data, pure Inertia/React render |

---

## Part 1 — Route Status

### Auth & Profile

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/login` | Fortify | `Auth/Login` | ✅ |
| POST | `/login` | Fortify | — | ✅ |
| GET | `/two-factor-challenge` | Fortify | `Auth/TwoFactorChallenge` | ✅ |
| POST | `/two-factor-challenge` | Fortify | — | ✅ |
| — | _(middleware intercept)_ | `RequireMfa` middleware | `Auth/mfa-required` | ✅ |
| GET | `/forgot-password` | Fortify | `Auth/ForgotPassword` | ✅ |
| POST | `/forgot-password` | Fortify | — | ✅ |
| GET | `/reset-password/{token}` | Fortify | `Auth/ResetPassword` | ✅ |
| POST | `/reset-password` | Fortify | — | ✅ |
| GET | `/user/confirm-password` | Fortify | `Auth/ConfirmPassword` | ✅ |
| POST | `/logout` | Fortify | — | ✅ |
| GET | `/dashboard/profile` | closure | `dashboard/profile` | ✅ |
| GET | `/dashboard/profile/mfa-setup` | closure | `dashboard/profile/mfa-setup` | ✅ |
| GET | `/dashboard/profile/notifications` | closure | `dashboard/profile/notifications-preferences` | ✅ |
| GET | `/dashboard/profile/api-tokens` | closure | `dashboard/profile/api-tokens` | ✅ |
| GET | `/dashboard/profile/sessions` | closure | `dashboard/profile/sessions` | ✅ |
| GET | `/dashboard/onboarding` | closure | `dashboard/onboarding` | ✅ |
| GET | `/dashboard/aide/glossaire` | closure | `dashboard/aide/glossaire` | ✅ |

### Marketing (static pages)

| Method | Path | Page | Status |
|--------|------|------|--------|
| GET | `/` | `index` | 📄 |
| GET | `/fonctionnalites` | `marketing/features` | 📄 |
| GET | `/conformite` | `marketing/compliance` | 📄 |
| GET | `/tarifs` | `marketing/tarifs` | 📄 |
| GET | `/clients` | `marketing/clients` | 📄 |
| GET | `/changelog` | `marketing/changelog` | 📄 |
| GET | `/contact` | `marketing/contact` | ✅ |
| POST | `/contact` | — | ✅ |
| GET | `/mentions-legales` | `marketing/legal-mentions` | 📄 |
| GET | `/confidentialite` | `marketing/privacy` | 📄 |
| GET | `/cgu` | `marketing/cgu` | 📄 |
| GET | `/accessibilite` | `marketing/accessibility` | 📄 |

### Platform Admin (`/admin` — `super_admin` role only)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/admin` | `AdminDashboardController::index` | `admin/dashboard/index` | ✅ |
| GET | `/admin/feature-flags` | `FeatureFlagController::index` | `admin/feature-flags/index` | ✅ |
| POST | `/admin/feature-flags/toggle` | `FeatureFlagController::toggle` | — | ✅ |
| GET | `/admin/system-health` | `SystemHealthController::index` | `admin/system-health/index` | ✅ |
| GET | `/admin/structures` | `AdminStructureController::index` | `admin/structures/index` | ✅ |
| GET | `/admin/structures/create` | `AdminStructureController::create` | `admin/structures/create` | ✅ |
| POST | `/admin/structures` | `AdminStructureController::store` | — | ✅ |
| GET | `/admin/structures/{structure}` | `AdminStructureController::show` | `admin/structures/show` | ✅ |
| GET | `/admin/structures/{structure}/audit-trail` | `AdminStructureController::auditTrail` | `admin/structures/audit-trail` | ✅ |
| GET | `/admin/structures/{structure}/edit` | `AdminStructureController::edit` | `admin/structures/edit` | ✅ |
| PUT | `/admin/structures/{structure}` | `AdminStructureController::update` | — | ✅ |
| DELETE | `/admin/structures/{structure}` | `AdminStructureController::destroy` | — | ✅ |
| POST | `/admin/structures/{structure}/suspend` | `AdminStructureController::suspend` | — | ✅ |
| POST | `/admin/structures/{structure}/reactivate` | `AdminStructureController::reactivate` | — | ✅ |

### Billing & Settings (tenant-scoped)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/billing` | `BillingController::show` | `dashboard/billing/index` | ✅ |
| POST | `/billing/change-plan` | `BillingController::changePlan` | — | 🔴 Stub |
| POST | `/billing/cancel` | `BillingController::cancel` | — | ✅ |
| GET | `/settings/structure` | `SettingsController::structure` | `dashboard/settings/structure` | ✅ |
| PUT | `/settings/contact` | `SettingsController::updateContact` | — | ✅ |

### Dashboard & Cross-cutting

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/dashboard` | `DashboardController::index` | `dashboard/index` | ✅ |
| GET | `/indicateurs` | `IndicateurController::index` | `dashboard/indicateurs/index` | ✅ |
| GET | `/audit-log` | `AuditLogController::index` | `dashboard/audit-log/index` | ✅ |
| GET | `/search/quick` | `SearchController::quick` | — (JSON) | ✅ |
| POST | `/notifications/{id}/read` | `NotificationController::markAsRead` | — | ✅ |
| POST | `/notifications/read-all` | `NotificationController::markAllRead` | — | ✅ |

---

### Phase 1 — M1 Bénéficiaires

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/beneficiaries` | `BeneficiaryController::index` | `dashboard/beneficiaries/index` | ✅ |
| GET | `/beneficiaries/create` | `BeneficiaryController::create` | `dashboard/beneficiaries/create` | ✅ |
| POST | `/beneficiaries` | `BeneficiaryController::store` | — | ✅ |
| GET | `/beneficiaries/{beneficiary}` | `BeneficiaryController::show` | `dashboard/beneficiaries/show` | ✅ |
| GET | `/beneficiaries/{beneficiary}/edit` | `BeneficiaryController::edit` | `dashboard/beneficiaries/edit` | ✅ |
| PUT | `/beneficiaries/{beneficiary}` | `BeneficiaryController::update` | — | ✅ |
| DELETE | `/beneficiaries/{beneficiary}` | `BeneficiaryController::destroy` | — | ✅ |
| GET | `/beneficiaries/{beneficiary}/dossier` | `BeneficiaryController::dossier` | `dashboard/beneficiaries/dossier` | ✅ |
| GET | `/beneficiaries/{beneficiary}/timeline` | `BeneficiaryController::timeline` | `dashboard/beneficiaries/timeline` | ✅ |
| GET | `/beneficiaries/{beneficiary}/contacts` | `BeneficiaryController::contacts` | `dashboard/beneficiaries/contacts` | ✅ |
| PUT | `/beneficiaries/{beneficiary}/contacts` | `BeneficiaryController::updateContacts` | — | ✅ |
| GET | `/beneficiaries/{beneficiary}/satisfaction` | `BeneficiaryController::satisfaction` | `dashboard/beneficiaries/satisfaction` | ✅ |
| POST | `/beneficiaries/{beneficiary}/satisfaction` | `BeneficiaryController::storeSatisfaction` | — | ✅ |

### Phase 1 — M1 Plans d'accompagnement

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/beneficiaries/{beneficiary}/care-plans` | `CarePlanController::indexForBeneficiary` | `dashboard/care-plans/index` | ✅ |
| GET | `/beneficiaries/{beneficiary}/care-plans/create` | `CarePlanController::createForBeneficiary` | `dashboard/care-plans/create` | ✅ |
| POST | `/beneficiaries/{beneficiary}/care-plans` | `CarePlanController::storeForBeneficiary` | — | ✅ |
| GET | `/care-plans/{carePlan}` | `CarePlanController::show` | `dashboard/care-plans/show` | ✅ |
| GET | `/care-plans/{carePlan}/edit` | `CarePlanController::edit` | `dashboard/care-plans/edit` | ✅ |
| PUT | `/care-plans/{carePlan}` | `CarePlanController::update` | — | ✅ |
| DELETE | `/care-plans/{carePlan}` | `CarePlanController::destroy` | — | ✅ |
| POST | `/care-plans/{carePlan}/activate` | `CarePlanController::activate` | — | ✅ |
| POST | `/care-plans/{carePlan}/archive` | `CarePlanController::archive` | — | ✅ |
| POST | `/care-plans/{carePlan}/copy` | `CarePlanController::copy` | — | ✅ |
| POST | `/care-plans/{carePlan}/tasks` | `PlannedTaskController::store` | — | ✅ |
| POST | `/care-plans/{carePlan}/tasks/reorder` | `PlannedTaskController::reorder` | — | ✅ |
| PUT | `/tasks/{task}` | `PlannedTaskController::update` | — | ✅ |
| DELETE | `/tasks/{task}` | `PlannedTaskController::destroy` | — | ✅ |

### Phase 1 — M1 Users / Intervenants

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/users` | `UserController::index` | `dashboard/users/index` | ✅ |
| GET | `/users/create` | `UserController::create` | `dashboard/users/create` | ✅ |
| POST | `/users` | `UserController::store` | — | ✅ |
| GET | `/users/{user}` | `UserController::show` | `dashboard/users/show` | ✅ |
| POST | `/users/{user}/deactivate` | `UserController::deactivate` | — | ✅ |
| POST | `/users/{user}/reactivate` | `UserController::reactivate` | — | ✅ |
| POST | `/beneficiaries/{beneficiary}/assignments` | `AssignmentController::store` | — | ✅ |
| DELETE | `/assignments/{assignment}` | `AssignmentController::destroy` | — | ✅ |

### Phase 1 — M1 Interventions (traçabilité)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/interventions` | `InterventionController::index` | `dashboard/interventions/index` | ✅ |
| GET | `/interventions/create` | `InterventionController::create` | `dashboard/interventions/create` | ✅ |
| POST | `/interventions` | `InterventionController::store` | — | ✅ |
| GET | `/interventions/{intervention}` | `InterventionController::show` | `dashboard/interventions/show` | ✅ |
| GET | `/interventions/{intervention}/edit` | `InterventionController::edit` | `dashboard/interventions/edit` | ✅ |
| PUT | `/interventions/{intervention}` | `InterventionController::update` | — | ✅ |
| DELETE | `/interventions/{intervention}` | `InterventionController::destroy` | — | ✅ |
| POST | `/interventions/{intervention}/checkin` | `InterventionController::checkIn` | — | ✅ |
| POST | `/interventions/{intervention}/checkout` | `InterventionController::checkOut` | — | ✅ |
| POST | `/interventions/bulk/cancel` | `InterventionController::bulkCancel` | — | ✅ |
| POST | `/interventions/{intervention}/cancel` | `InterventionController::cancel` | — | ✅ |
| POST | `/interventions/{intervention}/report` | `InterventionController::submitReport` | — | ✅ |
| POST | `/interventions/{intervention}/photos` | `InterventionController::storePhoto` | — | ✅ |
| DELETE | `/interventions/{intervention}/photos/{photo}` | `InterventionController::destroyPhoto` | — | ✅ |
| POST | `/interventions/{intervention}/signature` | `InterventionController::storeSignature` | — | ✅ |

### Phase 1 — M2 Incidents (événements indésirables)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/incidents` | `IncidentController::index` | `dashboard/incidents/index` | ✅ |
| GET | `/incidents/create` | `IncidentController::create` | `dashboard/incidents/create` | ✅ |
| POST | `/incidents` | `IncidentController::store` | — | ✅ |
| GET | `/incidents/{incident}` | `IncidentController::show` | `dashboard/incidents/show` | ✅ |
| PUT | `/incidents/{incident}` | `IncidentController::update` | — | ✅ |
| DELETE | `/incidents/{incident}` | `IncidentController::destroy` | — | ✅ |
| POST | `/incidents/{incident}/assign` | `IncidentController::assign` | — | ✅ |
| POST | `/incidents/{incident}/analyse` | `IncidentController::analyse` | — | ✅ |
| POST | `/incidents/{incident}/close` | `IncidentController::close` | — | ✅ |
| POST | `/incidents/{incident}/actions` | `IncidentController::storeAction` | — | ✅ |

---

### Phase 2 — M3 QVCT (hub via QvctController)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/qvct` | `QvctController::index` | `dashboard/qvct/index` | ✅ |
| GET | `/qvct/questionnaire` | `QvctController::questionnaire` | `dashboard/qvct/questionnaire` | ✅ |
| POST | `/qvct` | `QvctController::store` | — | ✅ |
| GET | `/qvct/weak-signals` | `QvctController::weakSignals` | `dashboard/qvct/weak-signals/index` | ✅ |
| POST | `/qvct/weak-signals/{id}/acknowledge` | `QvctController::acknowledgeWeakSignal` | — | ✅ |
| GET | `/qvct/indicators` | `QvctController::indicators` | `dashboard/qvct/indicators/index` | ✅ |
| GET | `/qvct/action-plans` | `QvctController::actionPlans` | `dashboard/qvct/action-plans/index` | ✅ |
| GET | `/qvct/journal` | `QvctController::journal` | `dashboard/qvct/journal/index` | ✅ |
| POST | `/qvct/journal` | `QvctController::storeJournalEntry` | — | ✅ |
| GET | `/qvct/exchanges` | `QvctController::exchanges` | `dashboard/qvct/exchanges/index` | ✅ |
| POST | `/qvct/exchanges` | `QvctController::storeExchange` | — | ✅ |

### Phase 2 — M3 QVCT Questionnaires

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/qvct/questionnaires` | `QvctQuestionnaireController::index` | `dashboard/qvct/questionnaires/index` | ❌ Missing page |
| GET | `/qvct/questionnaires/create` | `QvctQuestionnaireController::create` | `dashboard/qvct/questionnaires/create` | ✅ |
| POST | `/qvct/questionnaires` | `QvctQuestionnaireController::store` | — | ✅ |
| GET | `/qvct/questionnaires/{questionnaire}` | `QvctQuestionnaireController::show` | `dashboard/qvct/questionnaires/show` | ❌ Missing page |
| POST | `/qvct/questionnaires/{questionnaire}/archive` | `QvctQuestionnaireController::archive` | — | ✅ |
| DELETE | `/qvct/questionnaires/{questionnaire}` | `QvctQuestionnaireController::destroy` | — | ✅ |

### Phase 2 — M3 QVCT Campaigns

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/qvct/campaigns` | `QvctCampaignController::index` | `dashboard/qvct/campaigns/index` | ❌ Missing page |
| GET | `/qvct/campaigns/create` | `QvctCampaignController::create` | `dashboard/qvct/campaigns/create` | ✅ |
| GET | `/qvct/campaigns/{campaign}` | `QvctCampaignController::show` | `dashboard/qvct/campaigns/show` | ❌ Missing page |
| POST | `/qvct/questionnaires/{questionnaire}/campaigns` | `QvctCampaignController::launch` | — | ✅ |
| POST | `/qvct/campaigns/{campaign}/close` | `QvctCampaignController::close` | — | ✅ |

### Phase 2 — M6 Audits & Conformité

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/audits` | `AuditController::index` | `dashboard/audits/index` | ✅ |
| GET | `/audits/grids` | `AuditGridController::index` | `dashboard/audits/grids/index` | ✅ |
| GET | `/audits/grids/{auditGrid}` | `AuditGridController::show` | `dashboard/audits/grids/show` | ✅ |
| GET | `/audits/has-preparation` | `AuditController::hasPreparation` | `dashboard/audits/has-preparation` | ✅ |
| GET | `/audits/create` | `AuditController::create` | `dashboard/audits/create` | ✅ |
| POST | `/audits` | `AuditController::store` | — | ✅ |
| GET | `/audits/{audit}` | `AuditController::show` | `dashboard/audits/show` | ✅ |
| GET | `/audits/{audit}/edit` | `AuditController::edit` | `dashboard/audits/edit` | ✅ |
| PUT | `/audits/{audit}` | `AuditController::update` | — | ✅ |
| POST | `/audits/{audit}/finaliser` | `AuditController::finaliser` | — | ✅ |
| POST | `/audits/{audit}/cancel` | `AuditController::cancel` | — | ✅ |
| POST | `/audits/{audit}/ecarts` | `AuditController::storeEcart` | — | ✅ |
| DELETE | `/audits/{audit}/ecarts/{ecart}` | `AuditController::destroyEcart` | — | ✅ |

### Phase 2 — M6 Plans d'Amélioration Continue (PAC)

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/plans-amelioration` | `PlanAmeliorationController::index` | `dashboard/plans-amelioration/index` | ✅ |
| GET | `/plans-amelioration/create` | `PlanAmeliorationController::create` | `dashboard/plans-amelioration/create` | ✅ |
| POST | `/plans-amelioration` | `PlanAmeliorationController::store` | — | ✅ |
| GET | `/plans-amelioration/{plan}` | `PlanAmeliorationController::show` | `dashboard/plans-amelioration/show` | ✅ |
| GET | `/plans-amelioration/{plan}/edit` | `PlanAmeliorationController::edit` | `dashboard/plans-amelioration/edit` | ✅ |
| PUT | `/plans-amelioration/{plan}` | `PlanAmeliorationController::update` | — | ✅ |
| POST | `/plans-amelioration/{plan}/close` | `PlanAmeliorationController::close` | — | ✅ |
| POST | `/plans-amelioration/{plan}/cancel` | `PlanAmeliorationController::cancel` | — | ✅ |
| POST | `/plans-amelioration/{plan}/actions` | `PlanAmeliorationController::storeAction` | — | ✅ |
| PUT | `/plans-amelioration/{plan}/actions/{action}` | `PlanAmeliorationController::updateAction` | — | ✅ |
| POST | `/plans-amelioration/{plan}/actions/{action}/done` | `PlanAmeliorationController::markActionDone` | — | ✅ |
| DELETE | `/plans-amelioration/{plan}/actions/{action}` | `PlanAmeliorationController::destroyAction` | — | ✅ |

### Phase 2 — M4 Communication

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/communication` | `CommunicationController::index` | `dashboard/communication/index` | ✅ |
| POST | `/communication/groups/{group}/messages` | `CommunicationController::sendMessage` | — | ✅ |
| POST | `/communication/news` | `CommunicationController::publishNews` | — | ✅ |
| POST | `/communication/documents` | `CommunicationController::uploadDocument` | — | ✅ |
| GET | `/communication/documents/{document}/download` | `CommunicationController::downloadDocument` | — | ✅ |
| GET | `/communication/qa/{question}` | `CommunicationController::showQuestion` | `dashboard/communication/qa-show` | ✅ |
| POST | `/communication/qa` | `CommunicationController::askQuestion` | — | ✅ |
| POST | `/communication/qa/{question}/answers` | `CommunicationController::answerQuestion` | — | ✅ |
| POST | `/communication/qa/{question}/accept-answer` | `CommunicationController::acceptAnswer` | — | ✅ |
| POST | `/communication/qa/answers/{answer}/vote` | `CommunicationController::voteAnswer` | — | ✅ |

### Phase 2 — M5 Formations & Compétences

| Method | Path | Controller | Page | Status |
|--------|------|-----------|------|--------|
| GET | `/formations` | `FormationController::index` | `dashboard/formations/index` | ✅ |
| GET | `/formations/competencies/mine` | `FormationController::myCompetencies` | `dashboard/formations/competencies/mine` | ✅ |
| GET | `/formations/sessions/{id}` | `FormationController::showSession` | `dashboard/formations/sessions/show` | ✅ |
| POST | `/formations` | `FormationController::store` | — | 🔴 Stub |
| PUT | `/formations/{id}` | `FormationController::update` | — | 🔴 Stub |

---

## Summary of remaining work (Phase 0–2)

### ❌ Missing pages (hard 500 if navigated to)

1. `GET /qvct/questionnaires` → create `dashboard/qvct/questionnaires/index.tsx`
2. `GET /qvct/questionnaires/{questionnaire}` → create `dashboard/qvct/questionnaires/show.tsx`
3. `GET /qvct/campaigns` → create `dashboard/qvct/campaigns/index.tsx`
4. `GET /qvct/campaigns/{campaign}` → create `dashboard/qvct/campaigns/show.tsx`

### 🔴 Remaining stub write routes

5. `POST /formations` — no `TrainingPlan` create form exists on the web side yet; write goes through `/api/v1/competencies/training-plans`
6. `PUT /formations/{id}` — same
7. `POST /billing/change-plan` — requires Stripe payment-method flow; write goes through `/api/v1/billing/subscribe`

---

## Part 2 — Manual Testing Guide

### Prerequisites

```bash
# Start the full dev stack
composer run dev          # runs Laravel + Vite + queue in parallel

# Seed ALL test data (creates structure DEMO + all 10 accounts + rich sample data)
php artisan db:seed --class=DevSeeder

# Ensure MinIO is running for photo/document upload tests
# docker compose up -d minio   (if not already running)
```

Verify the app loads at `http://localhost:8000`.

---

### Test accounts

All accounts are created by `DevSeeder`. **Password for every account: `password`**

> Run `php artisan db:seed --class=DevSeeder` to create them. The seeder is idempotent — safe to re-run.

#### Structure DEMO (primary test structure — 7 users)

| Name | Email | Role | What they see |
|------|-------|------|---------------|
| Sophie Martin | `dirigeant@demo.fr` | `dirigeant` | Everything in DEMO structure — full KPIs, all beneficiaries, all interventions, all incidents, QVCT management, billing, users |
| Thomas Dupont | `coordinateur@demo.fr` | `coordinateur` | All beneficiaries in structure, all interventions, all incidents, care plans; NO QVCT management, NO billing |
| Claire Bernard | `qualite@demo.fr` | `referent_qualite` | Read-only interventions + beneficiaries; full audits + PAC; QVCT management (campaigns, weak signals); NO billing |
| Anne Petit | `rh@demo.fr` | `rh` | QVCT everything (manage campaigns, triage exchanges, action plans, weak signals); formations; user management; **NO beneficiaries, NO interventions, NO incidents, NO audits** |
| Marie Leclerc | `intervenant@demo.fr` | `intervenant` | **Only her own** assigned interventions + beneficiaries; incidents she declared; QVCT questionnaire + journal + exchanges only |
| Luc Moreau | `intervenant2@demo.fr` | `intervenant` | Same as Marie — **only his own** assigned data. Shares no interventions with Marie. |
| Platform Admin | `admin@platform.fr` | `super_admin` | No tenant — redirected to `/admin` surface only; sees all 4 structures; **cannot access any tenant page** |

#### Secondary structures (3 structures, 1 dirigeant each — for tenant isolation tests)

| Email | Structure | Role |
|-------|-----------|------|
| `dirigeant.soleil@demo.fr` | Soleil | `dirigeant` |
| `dirigeant.nord@demo.fr` | Nord | `dirigeant` |
| `dirigeant.mainsdor@demo.fr` | Mains d'Or | `dirigeant` |

These three accounts see **only their own structure's data**. They are invisible to DEMO users and vice versa.

---

### Data visibility matrix

Not all users see the same data — the system enforces column-level permission scoping per module.

| Module | `dirigeant` | `coordinateur` | `referent_qualite` | `rh` | `intervenant` |
|--------|-------------|----------------|--------------------|------|---------------|
| Dashboard | ✅ full KPIs | ✅ operational KPIs | ✅ operational KPIs | ✅ operational KPIs | ✅ (sees structure-wide KPIs on dashboard, own data on list pages) |
| Beneficiaries | ✅ all in structure | ✅ all in structure | ✅ read-only, all | ❌ no access | ⚠️ assigned only |
| Interventions | ✅ all in structure | ✅ all in structure | ✅ read-only, all | ❌ no access | ⚠️ own only |
| Incidents | ✅ all in structure | ✅ all in structure | ✅ read-only, all | ❌ no access | ⚠️ own only |
| Care plans | ✅ | ✅ full CRUD | ✅ read-only | ❌ | ✅ read-only |
| QVCT hub / aggregates | ✅ structure-level | ⚠️ team-level only | ✅ structure-level | ✅ structure-level | ❌ no aggregates |
| QVCT questionnaire | ✅ respond | ✅ respond | ✅ respond | ✅ respond | ✅ respond |
| QVCT management (campaigns, weak signals) | ✅ manage | ❌ | ✅ manage | ✅ manage | ❌ |
| Audits + PAC | ✅ view only | ✅ generate PAC | ✅ full manage | ❌ | ❌ |
| Formations | ✅ record | ✅ record | ❌ | ✅ plan + record | ✅ own certs only |
| Communication | ✅ post + moderate | ✅ post + moderate | ✅ post, upload | ✅ post + moderate | ✅ messages only |
| Users | ✅ full manage | ❌ | ❌ | ✅ manage | ❌ |
| Billing + Settings | ✅ | ❌ | ❌ | ❌ | ❌ |
| Audit log | ✅ structure-wide | ✅ own entries | ✅ own entries | ✅ own entries | ✅ own entries |
| `/admin/*` | ❌ (403 / 404) | ❌ | ❌ | ❌ | ❌ |

---

### Journey 1 — Coordinateur (core M1 flow)

> **Login as** `coordinateur@demo.fr` / `password` (Thomas Dupont)

#### 1.1 Dashboard
- URL: `http://localhost:8000/dashboard`
- Verify: KPI cards show real counts (not all zeros unless DB is empty).
- Verify: Recent incidents table renders, or empty state shows with a helpful message.
- Verify: Dark mode toggle (top-right) switches the entire layout without page reload.

#### 1.2 Bénéficiaires — list
- URL: `http://localhost:8000/beneficiaries`
- Verify: Table renders with pagination controls.
- Verify: Hover over a row's "Voir" button → `BeneficiaryHoverCard` appears with summary data.
- Verify: Click the avatar/name → navigates to `show`.
- Test: Click "Nouveau bénéficiaire" button → `QuickAddBeneficiaryModal` opens.

#### 1.3 Bénéficiaires — create
- URL: `http://localhost:8000/beneficiaries/create`
- Fill in nom, prenom, date_naissance, GIR (select 3), adresse, telephone.
- Submit → should redirect to `show` page with a success flash.
- Test validation: submit empty form → inline errors on required fields.

#### 1.4 Bénéficiaire — show
- URL: `http://localhost:8000/beneficiaries/{id}`
- Verify: Tabs visible — Info, Interventions, Contacts, Plan d'accompagnement, Satisfaction.
- Click each tab and verify it navigates to the right sub-page.

#### 1.5 Bénéficiaire — dossier médical
- URL: `http://localhost:8000/beneficiaries/{id}/dossier`
- Verify: Page loads; encrypted fields (notes médicales, allergies) show decrypted values.
- Verify: An audit log entry was created (check `/audit-log` after visiting).

#### 1.6 Bénéficiaire — timeline
- URL: `http://localhost:8000/beneficiaries/{id}/timeline`
- Verify: Timeline renders chronological events (interventions, incidents).
- Test with no data → empty state component shows.

#### 1.7 Bénéficiaire — contacts
- URL: `http://localhost:8000/beneficiaries/{id}/contacts`
- Verify: Contact cards render (personne de référence, médecin traitant).
- Edit a phone number → save → verify the update persists on reload.

#### 1.8 Bénéficiaire — satisfaction
- URL: `http://localhost:8000/beneficiaries/{id}/satisfaction`
- Verify: Rating history table renders.
- Submit a new rating (select 4 stars, add a comment) → success flash.

#### 1.9 Care plans — create & manage
- URL: `http://localhost:8000/beneficiaries/{id}/care-plans/create`
- Fill in titre, objectifs, frequency.
- Submit → redirect to `show` for the new plan.
- URL: `http://localhost:8000/care-plans/{id}`
- Add a task (click "Ajouter une tâche") → task appears in list.
- Drag-reorder tasks → reload → verify order persisted.
- Test: "Activer" button transitions plan status.
- Test: "Copier" creates a duplicate plan.

#### 1.10 Interventions — list
- URL: `http://localhost:8000/interventions`
- Verify: Stats bar at top shows counts (planifiées, en cours, réalisées, annulées).
- Test filter drawer: open, apply a status filter, verify table updates.
- Test: Cmd+K (or the palette icon) → `CommandPalette` opens, type an intervenant name → results appear.

#### 1.11 Interventions — create
- URL: `http://localhost:8000/interventions/create`
- Select an intervenant, a bénéficiaire, date/time.
- Submit → redirect to `show`.

#### 1.12 Interventions — lifecycle
- URL: `http://localhost:8000/interventions/{id}`
- Verify: Status badge correct (planifiée).
- Click "Démarrer" (check-in) → status changes to en_cours.
- Upload a photo via drag-drop → photo appears in the gallery.
- Submit a text report → report field saves.
- Click "Terminer" (check-out) → status changes to réalisée.
- Test "Annuler" on a planifiée intervention → confirmation dialog appears → confirm → status annulée.

#### 1.13 Bulk cancel interventions
- On the interventions list, select 2+ checkboxes → `BulkActionsToolbar` appears.
- Click "Annuler la sélection" → confirmation → all selected move to annulée.

---

### Journey 2 — Dirigeant (executive & admin flow)

> **Login as** `dirigeant@demo.fr` / `password` (Sophie Martin)

#### 2.1 Executive dashboard
- URL: `http://localhost:8000/dashboard`
- Verify: All 8 KPI cards render (interventions, incidents, score_conformite, score_qvct, etc.).
- Verify: `alertes_qvct` section shows empty state (QVCT not yet wired).

#### 2.2 Indicateurs M7 (demo mode)
- URL: `http://localhost:8000/indicateurs`
- In `local` env: charts and breakdown tables should show demo data.
- Verify: Line charts render (Chart.js), sparklines in KPI cards visible.
- Verify: Breakdown tabs (by intervenant, by bénéficiaire) switch correctly.

#### 2.3 Users — invite flow
- URL: `http://localhost:8000/users`
- Verify: User list renders with role badges.
- Click "Inviter un utilisateur" → form with role selector.
- Invite a new coordinateur with a valid email → success flash.
- URL: `http://localhost:8000/users/{id}`
- Verify: Profile card renders with role, permissions summary, last login.
- Test: "Désactiver" button shows confirmation dialog → confirm → user shows as inactive.
- Test: "Réactiver" reverses the state.

#### 2.4 Settings
- URL: `http://localhost:8000/settings/structure`
- Edit the structure nom or adresse → save → verify persistence.

#### 2.5 Billing (demo mode)
- URL: `http://localhost:8000/billing`
- In `local` env: subscription card, invoice list, payment method show demo data.
- Test: "Changer de plan" button → flash banner "en cours de développement" (expected — stub).
- Test: "Résilier" → flash banner (expected — stub).

#### 2.6 Audit log
- URL: `http://localhost:8000/audit-log`
- Verify: Table shows audit entries generated by the session's actions.
- Filter by model type (e.g., Beneficiary) → table narrows.

---

### Journey 3 — Référent qualité (audits & PAC)

> **Login as** `qualite@demo.fr` / `password` (Claire Bernard)

#### 3.1 Audit grids library
- URL: `http://localhost:8000/audits/grids`
- Verify: Grid cards render (HAS, ISO 9001, AFNOR NF X50-056).
- Click a grid → `http://localhost:8000/audits/grids/{id}` → items list with criteria.

#### 3.2 Create an audit
- URL: `http://localhost:8000/audits/create`
- Select referentiel (HAS), choose a grid, set a date.
- Submit → redirect to `show`.
- URL: `http://localhost:8000/audits/{id}`
- Verify: Audit detail shows criteria list from the selected grid.
- Add an écart (non-conformité) → écart appears in list.
- Click "Finaliser" → status changes to terminé.

#### 3.3 HAS preparation guide
- URL: `http://localhost:8000/audits/has-preparation`
- Verify: Conformity gaps table renders sorted by priority score.
- Verify: Each row shows the criterion, the gap, and the recommended action.

#### 3.4 PAC — create from scratch
- URL: `http://localhost:8000/plans-amelioration/create`
- Fill in titre, source (select manuel), echeance.
- Submit → redirect to `show`.
- Add an action: click "Ajouter une action", fill in description and responsable → save.
- Mark action as done → progress bar updates.
- Test: "Clôturer" button → confirm → status passes to terminé.

#### 3.5 PAC — full list
- URL: `http://localhost:8000/plans-amelioration`
- Verify: KPI cards show totals (open, en cours, terminés, taux completion).
- Verify: List cards show progression bar per plan.

---

### Journey 4 — RH (QVCT & formations)

> **Login as** `rh@demo.fr` / `password` (Anne Petit)

#### 4.1 QVCT hub (demo mode)
- URL: `http://localhost:8000/qvct`
- Verify: Campaign cards render (demo data in `local` env).
- Verify: Weak signal cards appear (WeakSignalCard components).
- Verify: Team breakdown table with score bars.
- Verify: Line chart showing QVCT trend over months.

#### 4.2 QVCT questionnaire (demo mode)
- URL: `http://localhost:8000/qvct/questionnaire`
- Verify: Campaign banner with titre and date_fin.
- Verify: LikertScale components render for each question.
- Verify: MoodSelector renders for mood-type question.
- Verify: AnonymityBanner is visible.
- Submit the form → flash "Module QVCT en cours de développement." (expected — stub).

#### 4.3 QVCT weak signals (demo mode)
- URL: `http://localhost:8000/qvct/weak-signals`
- Verify: Stats row (total, unacknowledged, critical, this_week).
- Verify: Signal cards render with severity badges.
- Click "Prendre en compte" → flash success (expected — demo stub).

#### 4.4 QVCT indicators heatmap (demo mode)
- URL: `http://localhost:8000/qvct/indicators`
- Verify: `RpsHeatmap` renders (5 dimensions × 5 teams grid, colour-coded).
- Verify: Dimension breakdown bar charts.

#### 4.5 QVCT action plans (demo mode)
- URL: `http://localhost:8000/qvct/action-plans`
- Verify: Plan cards with progress bars and item counts.
- Verify: Status badges (draft, published, closed).

#### 4.6 QVCT journal (demo mode)
- URL: `http://localhost:8000/qvct/journal`
- Verify: Journal entry cards with mood emoji and date.
- Verify: "Shared with RH" badge on entries that were shared.
- Submit new entry → flash success (expected — demo stub).

#### 4.7 QVCT exchanges (demo mode)
- URL: `http://localhost:8000/qvct/exchanges`
- Verify: Inbox tab shows incoming requests with status badges.
- Verify: Outbox tab shows sent requests.
- Submit new exchange request → flash success (expected — demo stub).

#### 4.8 Create a QVCT questionnaire (wired)
- URL: `http://localhost:8000/qvct/questionnaires/create`
- Fill in titre and questions (add Likert + mood + open-text blocks).
- Submit → should redirect to questionnaires index.
- **Note:** `/qvct/questionnaires` (index) will 500 — the page is missing. Navigate directly back to `/qvct` instead.

#### 4.9 Formations (demo mode)
- URL: `http://localhost:8000/formations`
- Verify: Certification expiry alerts render (colour-coded by days_to_expiry).
- Verify: Training plans table shows session counts and participant progress.
- Verify: "Bientôt expiré" badges on near-expiry rows.

#### 4.10 Formation session detail (demo mode)
- Click a session from the formations page → `http://localhost:8000/formations/sessions/{id}`
- Verify: Attendee list renders with registration status.

#### 4.11 My competencies (demo mode, as intervenant)
- Switch to `intervenant@demo.fr` / `password`.
- URL: `http://localhost:8000/formations/competencies/mine`
- Verify: Habilitations list (lifetime diplomas).
- Verify: Certifications list with expiry badges.
- Verify: Training enrollments.

---

### Journey 5 — Intervenant (restricted / scoped view)

> **Login as** `intervenant@demo.fr` / `password` (Marie Leclerc)
> Then repeat key steps as `intervenant2@demo.fr` / `password` (Luc Moreau) to confirm data isolation between the two intervenants.

This journey verifies that intervenants see **only their own data** — not the full structure view that coordinateurs and dirigeants get.

#### 5.1 Dashboard — KPI access
- URL: `http://localhost:8000/dashboard`
- Verify: The dashboard loads (intervenants are NOT blocked from the dashboard route).
- Verify: KPI numbers shown are the structure-wide aggregates — this is expected since the dashboard renders aggregate counts, not per-user filtered lists.
- **Note to flag:** If KPI counts feel wrong for an intervenant's context, it means `DashboardStatsService` may need a role-aware mode in a future sprint. Record the numbers for now.

#### 5.2 Beneficiaries — assignment scope
- URL: `http://localhost:8000/beneficiaries`
- Verify: Marie Leclerc sees **only the beneficiaries assigned to her** via `IntervenantAssignment`, not all 5 DEMO beneficiaries.
- Verify: "Nouveau bénéficiaire" button is **absent** (she lacks `beneficiaries.create`).
- Now logout → login as `intervenant2@demo.fr` → verify Luc Moreau sees a **different subset** of beneficiaries.
- Critical: neither intervenant should see the other's exclusively assigned beneficiaries.

#### 5.3 Interventions — own only
- URL: `http://localhost:8000/interventions`
- Verify: Marie sees only interventions where she is the `intervenant_id`.
- Verify: Bulk cancel checkbox is present — she can cancel her own planifiées.
- Logout → login as `intervenant2@demo.fr` → verify Luc sees his own different set.
- Critical: Marie must not see Luc's interventions.

#### 5.4 Incidents — own only
- URL: `http://localhost:8000/incidents`
- Verify: Only incidents Marie declared appear. The "Déclarer un incident" button should be present (`incidents.declare` is granted to intervenants).
- Verify: No "Assigner" or "Analyser" buttons on any incident (those require `incidents.analyze`).

#### 5.5 Blocked modules — expect 403 or empty
The following pages should either return 403 or render with empty state because Anne Petit's permissions don't apply; test that they don't crash:
- `http://localhost:8000/audits` — should render (read permission needed — intervenant lacks `audits.view`), expect redirect or 403
- `http://localhost:8000/plans-amelioration` — same
- `http://localhost:8000/users` — expect 403 (no `users.manage.structure`)
- `http://localhost:8000/billing` — expect 403 or redirect (no `structure.configure`)

#### 5.6 QVCT — respond-only view
- URL: `http://localhost:8000/qvct`
- Verify: Hub renders with the campaign list and questionnaire link.
- Verify: No "Gérer les campagnes" or "Signaux faibles" management buttons (she lacks `qvct.questionnaire.manage`).
- URL: `http://localhost:8000/qvct/questionnaire`
- Fill in the questionnaire and submit → flash (stub for now, expected).

#### 5.7 Communication — messages only
- URL: `http://localhost:8000/communication`
- Verify: Can read and send messages (has `messages.send`).
- Verify: No "Publier une actualité" button (lacks `newsfeed.post`).

---

### Journey 6 — Cross-cutting features

#### 6.1 Command palette (Cmd+K)
- Press `Cmd+K` (macOS) or `Ctrl+K` (Linux/Windows) from any page.
- Type a beneficiary name → results list updates.
- Click a result → navigates to the correct show page.
- Press `Esc` → palette closes.

#### 6.2 Hover cards
- On the interventions list, hover over an intervenant name → `UserHoverCard` appears.
- Hover over a beneficiary name → `BeneficiaryHoverCard` with GIR badge.
- On the incidents list, hover over an incident card → `IncidentHoverCard`.
- On the audits list, hover → `AuditHoverCard`.

#### 6.3 Preview sheets (slide-in panels)
- On beneficiaries list, click the "Aperçu" icon on a row → `BeneficiaryPreviewSheet` slides in from the right with summary data.
- On interventions list → `InterventionPreviewSheet`.
- On incidents list → `IncidentPreviewSheet`.
- Close the sheet → returns to list without navigation.

#### 6.4 MFA setup
- Login as `dirigeant@demo.fr` / `password` (MFA mandatory for this role).
- If MFA not configured, verify `MfaSetupBanner` appears at top of dashboard.
- URL: `http://localhost:8000/dashboard/profile/mfa-setup`
- Verify: QR code renders. Scan with TOTP app (Authy, 1Password, etc.).
- Enter the 6-digit code → setup confirmed.
- Logout and re-login → TOTP challenge page appears.

#### 6.5 Notifications centre
- Click the bell icon in the top nav → `NotificationsCenter` drawer opens.
- Mark a single notification as read.
- "Tout marquer comme lu" → all notifications clear.

#### 6.6 Theme toggle
- Click the sun/moon icon → switches between light and dark mode.
- Reload the page → preference is persisted (localStorage).
- Verify all pages look correct in dark mode (no invisible text, no broken contrast).

#### 6.7 Help drawer
- Click the `?` icon in the nav → `HelpDrawer` slides open with glossary shortcuts.
- URL: `http://localhost:8000/dashboard/aide/glossaire` — full Glossary page.
- Search a term (e.g., "GIR") → filtered results.

#### 6.8 Platform admin
- Login as `admin@platform.fr` / `password`.
- Verify: Redirected immediately to `/admin` (not `/dashboard` — this user has no tenant).
- URL: `http://localhost:8000/admin` → cross-tenant KPI dashboard.
- URL: `http://localhost:8000/admin/structures` → all structures list.
- Create a new structure → fill in nom, SIRET, type (SAAD), tier (Essentiel).
- Suspend a structure → verify badge changes.
- URL: `http://localhost:8000/admin/system-health` → service health table (DB, Redis, S3, queues).
- URL: `http://localhost:8000/admin/feature-flags` → toggle a Pennant flag → verify flash.

---

### Journey 7 — Tenant isolation (critical security check)

> This journey tests the `BelongsToStructure` global scope — the most security-critical property of the whole system. Run it after seeding `DevSeeder` (which creates 4 separate structures with data in each).

#### 7.1 Secondary structure cannot see DEMO data

1. Login as `dirigeant.soleil@demo.fr` / `password`.
2. URL: `http://localhost:8000/beneficiaries`
   - Verify: List shows **only** beneficiaries seeded for the "Soleil" structure.
   - The DEMO structure's beneficiaries (5 records) must be **completely absent**.
3. URL: `http://localhost:8000/interventions`
   - Same: only Soleil interventions.
4. URL: `http://localhost:8000/incidents`
   - Same: only Soleil incidents.
5. URL: `http://localhost:8000/audits`
   - Same: only Soleil audits.
6. URL: `http://localhost:8000/users`
   - Same: only Soleil users. Thomas Dupont (DEMO coordinateur) must not appear.

#### 7.2 DEMO structure cannot see secondary structure data

1. Logout → login as `dirigeant@demo.fr` / `password`.
2. Run the same checks as 7.1 in reverse: ensure DEMO pages contain no Soleil / Nord / Mains d'Or records.

#### 7.3 Platform admin sees all structures, not tenant data

1. Login as `admin@platform.fr` / `password`.
2. URL: `http://localhost:8000/admin/structures`
   - Verify: All 4 structures listed (DEMO, Soleil, Nord, Mains d'Or).
3. Attempt: `http://localhost:8000/beneficiaries`
   - Expect: Redirect to `/admin` or 403 — the platform admin has no `structure_id` so the tenant scope returns nothing and the controller should either redirect or show an empty page without crashing.
4. Attempt: `http://localhost:8000/dashboard`
   - Expect: Redirect to `/admin` (the `DashboardController` explicitly redirects `is_platform_admin` users).

#### 7.4 URL manipulation cannot cross tenants

1. Login as `dirigeant.soleil@demo.fr`.
2. Note a beneficiary UUID that belongs to the DEMO structure (get one from `dirigeant@demo.fr`'s session first).
3. Paste that UUID directly: `http://localhost:8000/beneficiaries/{demo-uuid}`
   - Expect: 404 — the `BelongsToStructure` global scope makes the record invisible; Laravel route model binding returns not-found, not a data leak.

---

### What NOT to test manually (broken routes)

Avoid navigating to these until the missing pages are created — they will throw a 500:

- `http://localhost:8000/qvct/questionnaires` (index missing)
- `http://localhost:8000/qvct/questionnaires/{any-id}` (show missing)
- `http://localhost:8000/qvct/campaigns` (index missing)
- `http://localhost:8000/qvct/campaigns/{any-id}` (show missing)

---

*Last updated: 2026-05-19 — verified against routes/web.php and pages on disk (Phase 0–2 only). Fixed: M1/M2 section labels for Interventions and Incidents; corrected `/confirm-password` path to `/user/confirm-password`; replaced mfa-required fake-route row with middleware-intercept note; removed stale M4 demo-fallback note.*
