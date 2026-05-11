# QualitéDomicile — System Overview

> **Reference spec:** CDC-QUALITE-DOM-2024-v2.0 (Cahier des Charges, Feb 2024)
> **Last updated:** 2026-05-05
> This document describes the full intended system — what is built, what is planned, and how every entity relates to every other. It covers the web admin application in depth.

---

## Table of Contents

1. [What Is QualitéDomicile?](#1-what-is-qualitedomicile)
2. [Structure Types — Who Uses It](#2-structure-types--who-uses-it)
3. [Subscription Tiers](#3-subscription-tiers)
4. [The Six Roles](#4-the-six-roles)
5. [The Web Admin Application](#5-the-web-admin-application)
6. [The Mobile Application](#6-the-mobile-application)
7. [All Platform Modules](#7-all-platform-modules)
8. [Security, Tenancy & Compliance](#8-security-tenancy--compliance)
9. [Commercial & Billing Model](#9-commercial--billing-model)
10. [Technical Architecture](#10-technical-architecture)
11. [Phased Roadmap](#11-phased-roadmap)

---

## 1. What Is QualitéDomicile?

QualitéDomicile is a multi-tenant SaaS platform for French home-care organisations. Its core purpose is to help these organisations manage, trace, and demonstrate the quality of the care they deliver — from the daily visit of an intervenant at a bénéficiaire's home all the way to the formal external evaluation by the HAS (Haute Autorité de Santé).

The platform addresses three overlapping obligations that every French home-care structure faces:

- **Regulatory traceability** — every intervention must be documented (who, when, what was done, any deviations). The ARS (Agence Régionale de Santé) can request this data at any time.
- **Quality certification** — HAS, ISO 9001, and AFNOR NF X50-056 evaluations require structured evidence of continuous improvement processes.
- **Staff wellbeing (QVCT)** — sector-wide legislation requires employers to monitor and act on psychosocial risks (RPS) for care workers, who face extremely high burnout rates.

QualitéDomicile brings all of these into one platform rather than requiring structures to use multiple disconnected tools (paper forms, spreadsheets, separate HR software).

---

## 2. Structure Types — Who Uses It

A **Structure** is the top-level tenant. Every piece of data belongs to exactly one structure. The platform supports six legal forms of home-care organisation, all subject to the same quality obligations but with different regulatory nuances:

| Code | Full name | Notes |
|------|-----------|-------|
| **SAAD** | Service d'Aide et d'Accompagnement à Domicile | The most common type. Provides non-medical in-home help (domestic tasks, meals, personal care for non-dependant clients). |
| **SSIAD** | Service de Soins Infirmiers à Domicile | Medically-supervised nursing care at home. Stricter clinical documentation requirements. |
| **SPASAD** | Service Polyvalent d'Aide et de Soins à Domicile | Combined SAAD + SSIAD. Dual authorisation. |
| **ESAD** | Équipe Spécialisée Alzheimer à Domicile | Alzheimer specialist teams attached to SSIADs. |
| **Mandataire** | Mandataire / Prestataire indépendant | Independent providers operating under mandate. |
| **CCAS** | Centre Communal d'Action Sociale / Collectivité | Municipal social services. Often multi-site. |

All six share the same data model and the same platform. Configuration differences (e.g., clinical fields only shown for SSIAD/SPASAD/ESAD) are handled by feature flags per tier and per structure type.

---

## 3. Subscription Tiers

Each structure subscribes to one of three tiers. The tier controls which modules are accessible and how much the structure pays per month.

| Tier | Monthly price/user | Modules unlocked |
|------|-------------------|-----------------|
| **Essentiel** | €8/intervenant | Core traceability (interventions, plans, beneficiaries), incidents, basic dashboard, mobile app, internal messaging |
| **Pro** | €15/intervenant | Everything in Essentiel + QVCT barometer, HAS/ISO audit grids, PAC generation, communication module, competencies & training, advanced reporting |
| **Premium** | €25/intervenant | Everything in Pro + AI predictive analytics (burnout risk, autonomy loss detection), beneficiary/family portal, sector benchmark reports, advanced ML-driven recommendations |

New structures begin with a **30-day free trial** (configurable). After trial expiry, access to gated features is blocked until a subscription is activated. Data is retained for 30 days after cancellation before permanent deletion.

---

## 4. The Six Roles

Every user belongs to exactly one structure and has exactly one role within it. Roles are enforced at three levels: the route middleware (authentication), the Policy layer (per-resource authorization), and the Form Request `authorize()` method.

---

### 4.1 Intervenant à domicile

The field worker who visits bénéficiaires at home. The most numerous user type. Typically works in zones with poor connectivity (rural areas, apartment basements).

**Primary environment:** Mobile app (React Native, offline-first).

**What they can do:**
- View their assigned tour for the day (list of interventions)
- Check in and check out of each intervention (geo-stamped)
- Fill in the intervention report (tasks completed, observations, voice-to-text)
- Capture photos and handwritten signatures
- Declare incidents at the bedside (under 2 minutes per CDC §4.2)
- Consult bénéficiaire care plan details (read-only)
- Send and receive messages within their structure
- View their own habilitations and certifications with expiry alerts
- Work fully offline and sync when connectivity returns

**What they cannot do:**
- Create or modify bénéficiaires
- View other intervenants' interventions
- Access QVCT data, audit grids, or PAC workflows
- Change any configuration

**MFA:** Optional. Device biometric unlocking the Sanctum token is the recommended mobile pattern.

---

### 4.2 Coordinateur / Responsable de secteur

The operational manager who oversees a group of intervenants and their assigned bénéficiaires. Works from the web admin, office-based.

**Primary environment:** Web admin (Inertia + React).

**What they can do:**
- Manage bénéficiaire dossiers (create, update, soft-delete, restore)
- Create and manage care plans (plans d'accompagnement) with recurring task templates
- Assign intervenants to bénéficiaires
- Plan intervention schedules (tournées)
- Monitor the live dashboard: who is checked in, who is late, who has not checked in
- View and manage incidents within their structure — assign, track corrective actions, close
- Validate or correct intervention reports
- Manage internal messages and news feed
- Invite new users to their structure
- Run QVCT campaign participation reports (Pro tier)
- Consult basic HR data (certifications, training attendance)

**What they cannot do:**
- Configure the structure (billing, tier, SIRET, etc.)
- Access or run HAS audit grids (that belongs to Référent qualité)
- See financial data
- Access other structures' data (enforced at DB level)

**MFA:** Mandatory. TOTP via authenticator app.

---

### 4.3 Dirigeant de structure

The director / managing leader of the structure. Has the widest operational permissions. Responsible for billing, configuration, and strategic decisions.

**Primary environment:** Web admin.

**What they can do:**
- Everything a Coordinateur can do
- Configure the structure (name, type, SIRET, billing email, address)
- Manage the Stripe subscription (subscribe, cancel, change tier)
- Invite and manage all users including other Coordinateurs
- View the executive dashboard (KPI trends, global conformity score, QVCT aggregate)
- Access all modules available on the structure's tier
- Consult audit reports and quality indicators
- Export data (CSV, PDF quality reports)
- Approve PAC (Plan d'Amélioration Continue) action items
- Set QVCT campaign parameters and trigger campaigns
- Manage the document library

**What they cannot do:**
- Access other structures' data
- Bypass tenant isolation in any way

**MFA:** Mandatory. TOTP via authenticator app.

---

### 4.4 Référent qualité

The internal quality manager. Specialised in audit, certification, and continuous improvement. May or may not be the same person as the Dirigeant in small structures.

**Primary environment:** Web admin.

**What they can do:**
- Run HAS evaluations, ISO 9001, AFNOR NF X50-056 audit grids
- Create and customise audit grids from the library
- Score audit runs and export evidence packages
- Generate PAC (Plan d'Amélioration Continue) automatically from audit gaps
- Track PAC action items to closure with deadlines and owners
- Prepare the HAS external evaluation dossier
- Access the full quality indicator history and trend charts
- Run sector benchmark comparisons (Premium)
- Manage the document library

**What they cannot do:**
- Modify billing or structure configuration (that is Dirigeant-only)
- Manage intervenants' daily tours

**MFA:** Mandatory. TOTP.

---

### 4.5 Responsable RH / Formation

The human resources or training manager. Focused on staff certifications, habilitations, and training plans.

**Primary environment:** Web admin.

**What they can do:**
- Manage habilitations (legal authorisations to perform specific care acts) per intervenant
- Manage certifications with expiry dates — receives automated alerts 60 and 30 days before expiry
- Create and manage training plans (plans de formation)
- Record training session attendance
- View QVCT psychosocial risk reports and weak-signal alerts (Pro)
- Consult QVCT individual RPS trajectories (anonymised at display layer)
- Manage the e-learning module (Phase 3 Premium)

**What they cannot do:**
- Manage intervention schedules or bénéficiaire dossiers
- Run audit grids
- Modify billing

**MFA:** Mandatory. TOTP.

---

### 4.6 Bénéficiaire / Famille (portail)

The care recipient or a family member. Limited read-only access to their own data. **Premium tier only, Phase 3.**

**Primary environment:** Separate beneficiary portal (Inertia PWA or lightweight Next.js app).

**What they can do:**
- View their own care plan (what services are scheduled and why)
- Consult their intervention history (who came, when, what was done)
- Complete satisfaction surveys after interventions
- Report incidents directly (from the family's perspective)
- Contact the structure's coordinator via a secure message channel

**What they cannot do:**
- View any other bénéficiaire's data
- Access internal platform features
- Modify their care plan

**Auth:** Signed time-limited URLs for family members (no account required); full account with password for bénéficiaires who manage their own access.

---

## 5. The Web Admin Application

The web admin is an **Inertia + React + TypeScript + Tailwind v4** single-page application served from the Laravel backend. It is session-authenticated (Fortify) and is used exclusively by office-based roles: Coordinateur, Dirigeant, Référent qualité, and Responsable RH.

The application is a true admin SPA: no page reloads between navigation, optimistic updates where safe, real-time updates via Reverb WebSockets.

---

### 5.1 Authentication & MFA Flow

1. User navigates to `/login` → credential form (email + password)
2. Fortify validates credentials
3. If the user's role requires MFA (all office roles):
   - First login ever: `/mfa-required` enrollment screen — user must set up a TOTP authenticator app
   - Subsequent logins: `/two-factor-challenge` — 6-digit code entry
4. After MFA confirmation: redirect to dashboard
5. Session timeout: configurable (default 2 hours idle)
6. Password reset: email link → `/password/reset`
7. Confirm password: required before sensitive operations (billing changes, user deletion)

---

### 5.2 Navigation Structure

The sidebar adapts to the user's role — sections the user has no permission to see are hidden entirely (not just greyed out).

```
Dashboard
├── Vue d'ensemble (KPIs, alerts, live intervention map)
├── Tableau opérationnel (today's tours, live status)
└── Indicateurs qualité (trend charts, conformity score)

Bénéficiaires
├── Liste
├── Nouveau bénéficiaire
└── [Fiche bénéficiaire]
    ├── Informations générales
    ├── Plan d'accompagnement
    ├── Historique des interventions
    ├── Incidents
    └── Documents

Interventions
├── Planning
├── Aujourd'hui
└── Historique

Incidents
├── Liste (filtrable par statut / gravité)
├── Déclarer un incident
└── [Fiche incident]
    ├── Analyse 5-pourquoi
    ├── Actions correctives
    └── Suivis

Qualité  [Pro+]
├── Grilles d'audit
├── Évaluations (runs)
├── Plans d'amélioration continue (PAC)
└── Préparation HAS

QVCT  [Pro+]
├── Baromètre (campagnes)
├── Indicateurs QVCT
├── Signaux faibles
├── Journal de bord (individuel)
└── Demandes d'échange

Communication
├── Messagerie
├── Fil d'actualité
├── Forum Q&R
└── Bibliothèque documentaire

RH & Formation  [Pro+]
├── Habilitations
├── Certifications
├── Plans de formation
└── Sessions de formation

IA & Prédictif  [Premium]
├── Risques burnout
├── Perte d'autonomie
└── Recommandations

Administration
├── Structure (paramètres généraux)
├── Utilisateurs
├── Abonnement & facturation
└── Sécurité (accès, logs)
```

---

### 5.3 Dashboard

The dashboard is the first screen after login. It is role-differentiated:

**Dirigeant / Référent qualité view (executive)**
- KPI tiles: total interventions this month, total incidents open, conformity score (%), QVCT satisfaction score
- Trend charts: interventions per week (12 weeks rolling), incidents by category, conformity score evolution
- Alert list: certifications expiring this month, open critical incidents, intervenants with no tour today
- Billing status tile: current tier, trial days remaining (if applicable), next billing date

**Coordinateur view (operational)**
- Live tour overview: list of today's interventions with real-time status (planned / checked-in / completed / late / missing)
- Alert list: missing check-ins, declared incidents requiring assignment
- Intervenant status map (Phase 2+)

The dashboard data is cached in Redis per structure (5-minute TTL) and invalidated on any write to the relevant tables.

---

### 5.4 Bénéficiaire Management

**List view**
- Searchable, filterable, paginated
- Columns: name, date of birth, GIR level, assigned intervenant, last intervention date, status
- Quick actions: view, edit, new intervention

**Create / Edit form**
- Personal details: name, date of birth, address, phone, family status
- Clinical: GIR level (1–6, indicates dependency level), treating physician, emergency contact
- Sensitive fields (encrypted at rest): medical notes, allergies, specific care instructions
- RGPD: explicit consent timestamp recorded on creation

**Fiche bénéficiaire (detail view)**
Tabbed layout:
1. **Informations** — all personal/clinical fields, read-only with Edit button
2. **Plan d'accompagnement** — the care plan defining what tasks an intervenant performs on each visit (frequency, duration, responsible person). Editing creates a new plan version; old versions are archived.
3. **Interventions** — full history with filter by date, intervenant, status. Click any row to open the intervention detail (tasks completed, report, photos, signature).
4. **Incidents** — incidents declared in relation to this bénéficiaire. Filterable by status and gravity.
5. **Documents** — dossier-specific documents (medical prescriptions, assessment reports, etc.)

---

### 5.5 Intervention Management

**Planning view**
- Calendar / agenda view of all scheduled interventions per intervenant per day
- Drag-and-drop to reschedule (planned)
- Bulk assignment: assign a weekly recurring pattern to an intervenant for a bénéficiaire

**Live aujourd'hui view**
- Real-time list refreshed via Reverb WebSocket
- Color-coded: grey (planned), orange (in progress), green (completed), red (late / missing)
- Click any intervention to see details, send a message to the intervenant

**Intervention detail**
- Basic metadata: bénéficiaire, intervenant, planned vs. actual times
- Check-in coordinates (displayed on a static map)
- Tasks realised: checkboxes from the care plan, pre-filled by the intervenant on mobile
- Rapport: text or voice transcript
- Photos: grid of uploaded photos (served via signed S3 URLs, expire after 1 hour)
- Signature: the bénéficiaire's handwritten signature
- Edit report: Coordinateur can correct or supplement the intervenant's report

---

### 5.6 Incident Management

Every incident goes through a defined lifecycle enforced by the state machine in `IncidentService`:

```
déclaré → en_analyse → plan_actions → clos
                    ↘ (can be escalated to ARS if grave/critique)
```

**List view**
- Filterable by: status, gravity (mineur / significatif / grave / critique), category, date range, intervenant, bénéficiaire
- Badge counts: open, en_analyse, awaiting closure
- Export to CSV or PDF

**Incident categories (French sector standard)**
- Chute (fall)
- Agression (assault on or by the bénéficiaire)
- Erreur médicamenteuse (medication error)
- Maltraitance suspectée (suspected abuse — triggers mandatory ARS notification)
- Situation de danger (dangerous situation)
- Autre (other)

**Gravity classification**
Computed automatically by `GraviteClassifier` (pure function) based on category + context fields. Can be overridden by the Coordinateur with justification:
- Mineur: no physical harm, no immediate risk
- Significatif: minor harm, corrective action required
- Grave: significant harm or high-risk situation — ARS notification email sent automatically
- Critique: immediate danger or death — emergency ARS protocol triggered

**Fiche incident**
1. **Déclaration** — who, what, when, where, category, initial gravity, description
2. **Analyse 5-pourquoi** — structured root cause analysis form, guided questions per category
3. **Actions correctives** — CRUD for corrective actions, each with an owner (user) and deadline
4. **Suivis** — progress updates logged chronologically until closure
5. **ARS notification** — if applicable, the email sent to the ARS is displayed for traceability

---

### 5.7 Quality Module (Pro+)

#### Audit Grids

The platform ships with a library of standard grids:
- HAS (Haute Autorité de Santé) — the French national external evaluation grid
- ISO 9001:2015 — general quality management
- AFNOR NF X50-056 — sector-specific French home-care standard

Structures can also create custom grids or clone and modify library grids.

**Grid anatomy:** A grid is composed of axes → sub-axes → items. Each item has a question, a scoring method (0/1/2 or percentage compliance), and evidence guidance text.

**Running an audit**
- Create an "Audit Run" linked to a grid
- Assign items to auditors (Référent qualité, Coordinateur, Dirigeant can all participate)
- Score each item with evidence notes and optional attached documents
- The system computes: item scores → axis scores → global conformity score
- Generate a gap report: items below threshold are flagged as non-conformities

**PAC (Plan d'Amélioration Continue)**
Generated automatically from the audit run's non-conformities. Each non-conformity becomes a PAC action item with:
- Root cause (linked to the 5-pourquoi if an incident existed)
- Action to take
- Responsible person
- Deadline
- Indicator of success

PAC action items are tracked to closure. The overall PAC completion rate is surfaced on the dashboard.

**HAS Preparation Guide**
A structured wizard that walks the Référent qualité through the self-assessment required before the HAS external evaluation. Produces a downloadable dossier.

---

### 5.8 QVCT Module (Pro+)

QVCT = Qualité de Vie et des Conditions de Travail. This module addresses the legal requirement for home-care employers to monitor and improve staff wellbeing.

#### Baromètre (questionnaire campaigns)

The Dirigeant or Référent qualité creates a campaign:
- Select or create a questionnaire (satisfaction + wellbeing dimensions)
- Set frequency: one-shot, weekly, monthly, or quarterly
- Set start date and reminder schedule

Intervenants receive the questionnaire on their mobile app. Responses are stored anonymously (no individual response is attributable to any person by any user). The platform only aggregates.

#### Weak-signal detection

`WeakSignalDetector` (a pure PHP service) runs automatically after each response batch closes. It analyses:
- Average morale score per team / sector
- Trend: is the score dropping over multiple periods?
- Overload signals: do intervenants report feeling overloaded?
- Relational conflict signals

When a threshold is crossed, a `QvctWeakSignal` is recorded and the Responsable RH / Référent qualité receives a notification. The notification identifies the team and the signal type but never a specific individual.

#### QVCT Indicators

Structured KPI tracking for the sector's mandatory wellbeing indicators:
- Absenteeism rate (% hours lost)
- Turnover rate (annual departures / headcount)
- Work accident rate (per 1000 hours worked)
- Baromètre satisfaction score (rolling average)
- Perceived overload index

Each indicator is entered manually or, for some, computed automatically from existing platform data (e.g., turnover from user deactivation events).

#### Individual RPS tracking

Each intervenant can maintain an optional private journal (`QvctJournalEntry`) — free text, visible only to themselves. They can also submit a confidential exchange request (`QvctExchangeRequest`) to speak with the Responsable RH or Référent qualité, which opens a private messaging thread.

#### Psychosocial risk cartography

A visual heat-map per team / sector, built from anonymised aggregated signals. Shows which teams are at higher risk. Used by the Dirigeant to prioritise management interventions.

---

### 5.9 Communication Module

#### Messagerie (internal messaging)

Private 1-to-1 and group messaging between users of the same structure. Real-time delivery via Reverb (WebSocket). Intervenants use this from the mobile app; office staff from the web admin.

**Discussion groups:** A Coordinateur can create a group (e.g., "Secteur Nord" or "Équipe Sophie") and add members. Messages in the group are visible to all members.

#### Fil d'actualité (news feed)

A structured news board for structure-wide announcements. Only Coordinateurs, Dirigeants, and Référents qualité can publish. Intervenants read. Posts support rich text and attached documents.

#### Forum Q&R

A structured Q&A forum where any user can ask a question and receive answers from other users or managers. Useful for protocol questions ("What do I do if the bénéficiaire is not at home?").

#### Bibliothèque documentaire

Centralised document storage per structure:
- Protocol documents
- Training materials
- Administrative forms
- Audit evidence
- Annual quality reports

Documents are stored in S3 with encryption. Access is controlled by role. Version history is maintained.

---

### 5.10 RH & Formation Module (Pro+)

#### Habilitations

Legal authorisations that define which care acts an intervenant is permitted to perform. Examples: medication reminder, wound dressing, shower assistance for GIR 1-2 bénéficiaires.

- Each habilitation has a type, a start date, and optionally an expiry date
- The Responsable RH tracks habilitations per intervenant
- When assigning an intervenant to a bénéficiaire, the system warns if the intervenant lacks the required habilitations for the care plan's tasks

#### Certifications

Professional certifications (DEAVS, AES, ADVF, CQP, etc.) with expiry dates.

- Automated alerts at 60 and 30 days before expiry, sent to the Responsable RH and to the intervenant directly
- Dashboard tile shows count of certifications expiring this month
- Expired certifications are flagged on the intervenant's profile

#### Training Plans

The Responsable RH creates an annual training plan per structure:
- Identify training needs per role or per individual
- Create training sessions with date, location, trainer, duration
- Record attendance per session
- Link attendance to the relevant certification or competency
- Export attendance records (required for OPCO funding claims)

---

### 5.11 Administration

#### Structure settings (Dirigeant only)

- Legal details: name, type, SIRET, address, billing email
- Operational settings: default trial period length, notification preferences

#### User management

- Invite new users by email (generates a time-limited password setup link)
- Assign and change roles
- Deactivate / reactivate users (soft-delete; their historical data is preserved)
- View last login, MFA status, active sessions

#### Subscription & billing (Dirigeant only)

- Current tier and per-seat price
- Number of active intervenants (seat count)
- Stripe subscription status (trialing / active / past_due / canceled)
- Upgrade / downgrade tier
- Cancel subscription (triggers 30-day data retention notice)
- Payment method management (handled entirely by Stripe, no card numbers stored on platform)

#### Security logs

- Audit log viewer: who did what, when, on which record
- Failed MFA attempts
- Session events (login, logout, session timeout)
- Permission-denied events

---

## 6. The Mobile Application

**Technology:** React Native (offline-first), served via TestFlight (iOS) and Play Store / internal APK (Android).

**Auth:** Sanctum personal access token stored in the device's secure keychain. Token issued after web login; MFA is optional for intervenants but recommended.

**Offline strategy:** Local SQLite database mirrors the server schema for the intervenant's own data. When offline:
- The intervenant can view their full tour and all bénéficiaire details for the day
- Check-in, check-out, reports, photos, and incident declarations are stored locally with a pending-sync flag
- On reconnect, `POST /api/v1/sync/batch` replays all pending operations to the server in order

**Key screens:**
- Today's tour (chronological list of interventions)
- Intervention detail: task checklist, report entry, photo capture, signature pad
- Incident declaration form (< 2 minutes to complete per CDC §4.2)
- Messagerie
- My profile (certifications, habilitations, expiry alerts)
- QVCT questionnaire (when a campaign is active)

---

## 7. All Platform Modules

| Module | Phase | Tier | Web | Mobile |
|--------|-------|------|-----|--------|
| Traçabilité interventions | Phase 1 M2 | All | ✓ | ✓ |
| Bénéficiaires & plans | Phase 1 M1 | All | ✓ | Read |
| Incidents | Phase 1 M3 | All | ✓ | ✓ |
| Dashboard basique | Phase 1 M7 | All | ✓ | — |
| Mobile offline sync | Phase 1 M4 | All | — | ✓ |
| QVCT baromètre | Phase 2 M3 | Pro+ | ✓ | ✓ (survey) |
| Audits & conformité | Phase 2 M6 | Pro+ | ✓ | ✓ (execution) |
| PAC (amélioration continue) | Phase 2 M6 | Pro+ | ✓ | — |
| Communication (messagerie, news, Q&A) | Phase 2 M4 | All | ✓ | ✓ |
| Bibliothèque documentaire | Phase 2 M4 | All | ✓ | Read |
| Compétences & habilitations | Phase 2 M5 | Pro+ | ✓ | Read |
| Plans de formation | Phase 2 M5 | Pro+ | ✓ | — |
| Facturation & abonnement | Phase 2 C1-C7 | All | ✓ | — |
| IA prédictive (burnout, autonomie) | Phase 3 M9 | Premium | ✓ | Alert |
| Portail bénéficiaire / famille | Phase 3 M8 | Premium | Portal | — |
| Benchmark anonymisé | Phase 3 | Premium | ✓ | — |
| Reporting annuel qualité (PDF) | Phase 3 | Pro+ | ✓ | — |
| Connecteurs logiciels de planning | Phase 4 | All | API | — |
| Licences institutionnelles (ARS, CD) | Phase 4 | Custom | ✓ | — |
| Multi-pays (Belgique, Suisse, Lux.) | Phase 5 | All | ✓ | ✓ |

---

## 8. Security, Tenancy & Compliance

### Multi-tenancy (row-level isolation)

Every table in the domain carries a `structure_id` UUID foreign key. Every Eloquent model that carries domain data uses the `BelongsToStructure` trait, which:
1. Injects a global scope — every query automatically filters by the current tenant's `structure_id`
2. Sets `structure_id` on creation from the authenticated user
3. Runs in `boot()` to fail loudly if someone declares a model without the column

The `BasePolicy::before()` method adds a second layer: even if the global scope somehow failed, the policy denies access to any model whose `structure_id` does not match the authenticated user's `structure_id`.

Cross-tenant queries are only permitted through `CrossTenantQueryService`, which requires explicit code review, logs every call, and is used only for the sector benchmark (Phase 3).

### Security architecture

| Concern | Mechanism |
|---------|-----------|
| Auth (web) | Laravel Fortify (session cookie) |
| Auth (API) | Sanctum personal access tokens |
| MFA | TOTP (RFC 6238) — mandatory for office roles |
| Audit logging | `owen-it/laravel-auditing` — every write to health data creates an audit entry with user, timestamp, before/after |
| Encryption at rest | S3 SSE-KMS for files; PostgreSQL column encryption for sensitive text fields (medical notes, allergies) |
| Encryption in transit | TLS 1.2+ everywhere; HSTS enforced |
| Security headers | CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy on every response |
| Rate limiting | Per-user and per-IP; tighter on auth endpoints |
| Input validation | Every write goes through a Form Request; never inline in controllers |
| Injection prevention | Eloquent ORM exclusively; raw SQL banned except in the approved CrossTenantQueryService |
| Photo upload | EXIF stripped and re-encoded via Intervention Image before S3 storage |
| RGPD erasure | `BeneficiaireService::anonymize()` pseudonymises personal data on erasure request |

### HDS (Hébergement de Données de Santé)

French law requires that health data be stored with an HDS-certified host. The platform is deployed on AWS Paris (eu-west-3) or OVHcloud France, both HDS-certified. The data classification guide in `references/compliance/` defines which fields are health data and which are operational data.

### Compliance checklist applied at every PR

1. Architecture & code organisation
2. Security (OWASP Top 10 + HDS/RGPD)
3. Database & data layer
4. Multi-tenancy isolation
5. API design & contracts
6. Performance & scalability
7. Reliability & resilience
8. Observability
9. Background processing
10. Testing strategy
11. Deployment & operations
12. Compliance, governance & team

---

## 9. Commercial & Billing Model

### Pricing

- Per-intervenant seat, billed monthly
- Essentiel: €8/seat/month — €96/year
- Pro: €15/seat/month — €180/year
- Premium: €25/seat/month — €300/year

A structure with 20 intervenants on Pro pays €300/month.

### Revenue trajectory (CDC §7.3)

| Phase | Structures | Intervenants | ARR (Pro estimate) |
|-------|-----------|-------------|-------------------|
| Phase 1 | 10 | 200 | ~€36K |
| Phase 2 | 50 | 1,000 | ~€180K |
| Phase 3 | 200 | 5,000–15,000 | ~€900K–€2.7M |
| Phase 4 | 500 | 12,500–37,500 | ~€2.25M–€6.75M |
| Phase 5 | 3,000 | 75,000+ | ~€18M ARR target |

### Technical billing stack

- **Stripe** — payment processing, subscription management, invoice generation, Stripe Tax for French TVA
- **Laravel Cashier** (v16) — PHP wrapper; `Structure` is the Cashier billable entity (not the individual user)
- **Stripe webhook** — `POST /stripe/webhook` (Cashier built-in route) receives subscription lifecycle events; `SyncSubscriptionToStructure` listener keeps `structures.tier` in sync
- **CRM** — log-only stub (Phase 2); full HubSpot integration (Phase 3) for Pro upgrade events and cancellations

### Subscription lifecycle

```
Provision structure
       ↓
  30-day free trial (Essentiel tier)
       ↓
  Add payment method → subscribe to Pro/Premium
       ↓
  Trialing period (if trial_days > 0)
       ↓
  Active (charged monthly)
       ↓
  Cancel → Grace period (access until period end)
       ↓
  Data retained 30 days → Purge
```

---

## 10. Technical Architecture

### Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+, PostgreSQL 16, Redis 7 |
| Web frontend | Inertia v2 + React 19 + TypeScript + Tailwind v4 |
| Mobile frontend | React Native (offline-first, SQLite) |
| API | REST under `/api/v1/*`, Sanctum tokens, OpenAPI via Scramble |
| Real-time | Laravel Reverb (WebSocket) |
| File storage | S3-compatible (AWS Paris or OVHcloud France), SSE-KMS |
| Queue / cache | Redis |
| Error tracking | Sentry |
| Payments | Stripe + Laravel Cashier v16 |
| RBAC | Spatie Laravel Permission (team-scoped) |
| Audit | owen-it/laravel-auditing |
| Feature flags | Laravel Pennant |
| OpenAPI docs | Dedoc Scramble |
| AI / ML (Phase 3) | Python microservice (scikit-learn + LLM) behind REST/gRPC |

### Data flow for a typical intervention

```
[Intervenant mobile] → offline SQLite
         ↓ (on reconnect)
POST /api/v1/sync/batch [Idempotency-Key header]
         ↓
SyncBatchService (transaction per operation)
         ↓
InterventionService.checkOut(intervention, report, photos)
         ↓
InterventionObserver.updated() → broadcast InterventionStatusChanged on Reverb
         ↓
[Coordinateur web admin] → receives real-time update without page reload
         ↓
AuditLog entry created (who, when, what changed)
         ↓
Redis cache invalidated → next dashboard load fetches fresh data
```

### Background jobs

All operations exceeding 200ms run as queued jobs:
- `CertificationExpiryAlertJob` — daily; notifies RH + intervenant of upcoming expiry
- `NotifyResponsableSecteurJob` — fires on every incident declaration
- `NotifyARSJob` — fires on grave/critique incidents
- `NotifyReferentRhJob` — fires on QVCT weak signal detection
- `StructureWelcomeNotification` (ShouldQueue) — dispatched on structure provision
- `SubscriptionCancelledNotification` (ShouldQueue) — dispatched on subscription cancel
- Future: `BurnoutRiskPredictionJob` (Phase 3) — sends bénéficiaire data to Python ML service

All jobs are idempotent and tenant-context-preserving (they carry `structure_id` in their payload).

---

## 11. Phased Roadmap

### Phase 0 — Foundation (3 weeks)

Infrastructure, tooling, multi-tenancy scaffold, RBAC foundation, audit logging, CI/CD. No business features yet. Delivered before any Phase 1 feature work begins.

**Done.**

---

### Phase 1 — MVP (Months 1–4, target: 10 structures)

| Module | Description |
|--------|-------------|
| M1 — Bénéficiaires & plans | Full bénéficiaire dossier management, care plans, intervenant assignments |
| M2 — Traçabilité interventions | Check-in/out, reports, photos, signatures, real-time coordinator dashboard |
| M3 — Gestion des incidents | Declaration, auto-classification, ARS notification, 5-pourquoi, corrective actions, closure |
| M7 — Dashboard basique | KPI tiles and operational live view for coordinators and dirigeants |
| M4 — Mobile API & app | Full REST API, React Native mobile app skeleton, offline sync protocol |

**Done — 10 pilot structures onboarded.**

---

### Phase 2 — Qualité / Pro offering (Months 5–8, target: 50 structures)

| Module | Description |
|--------|-------------|
| M3 — QVCT baromètre | Anonymous questionnaire campaigns, weak-signal detection, RPS tracking, individual journal, exchange requests, indicator tracking, cartography |
| M6 — Audits & conformité | Grid library (HAS, ISO, AFNOR), mobile audit execution, auto-scoring, PAC generation, HAS preparation guide |
| M4 — Communication | Real-time messaging, discussion groups, news feed, Q&A forum, document library |
| M5 — Compétences & formation | Habilitation tracking, certification expiry alerts, training plans, session attendance |
| C1–C7 — Commercial readiness | Stripe/Cashier subscription, webhook idempotency, onboarding automation, CRM stub, self-service cancel, full lifecycle tests |

**All C1–C7 done. M3/M4/M5/M6 backend done. Frontend in progress.**

---

### Phase 3 — IA & Data / Premium offering (Months 9–14, target: 200 structures)

- **M9 — IA prédictive:** Python microservice for burnout risk, autonomy loss detection, semantic analysis of intervention reports
- **M8 — Portail bénéficiaire:** Separate lightweight app for bénéficiaires and families
- **Benchmark anonymisé:** Sector-wide anonymised comparisons across all structures
- **Reporting engine:** Auto-generated annual quality report PDF per structure

---

### Phase 4 — Expansion nationale (Months 15–24, target: 500 structures)

- API connectors for planning software widely used in the sector
- Institutional licences for Conseils Départementaux and ARS
- Partner integrator ecosystem with sandbox API
- Certification qualité partenaire label for high-performing structures

---

### Phase 5 — Expansion européenne (Months 25–36, target: 1,000–3,000 structures)

- Multi-region deployment: France, Belgium, Switzerland, Luxembourg
- Per-country data residency (Swiss nLPD, Belgian GDPR adaptations)
- Localised UI: French variants per country; Dutch (Flanders); German (Switzerland)
- €18M ARR target

---

## Summary: Entity Relationships at a Glance

```
Structure (tenant)
│
├── Users
│   ├── Dirigeant         → full admin + billing
│   ├── Coordinateur      → operational management
│   ├── Référent qualité  → audit + quality
│   ├── Responsable RH    → HR + training
│   ├── Intervenant       → field visits (mobile)
│   └── Bénéficiaire portal → read-only care data (Phase 3)
│
├── Bénéficiaires
│   └── CarePlans → PlannedTasks
│
├── Interventions
│   ├── CompletedTasks
│   ├── Photos
│   └── Signatures
│
├── Incidents
│   ├── ActionsCorrectives
│   └── Suivis
│
├── QVCT
│   ├── Campaigns → Questionnaires → Responses (anonymous)
│   ├── WeakSignals
│   ├── JournalEntries
│   ├── ExchangeRequests
│   └── Indicators
│
├── Audits
│   ├── AuditGrids → AuditGridItems
│   ├── AuditRuns → AuditRunResponses
│   └── PACs → PacActions
│
├── Communication
│   ├── DiscussionGroups → Messages
│   ├── NewsFeedPosts
│   ├── QaQuestions → QaAnswers
│   └── Documents
│
├── RH & Formation
│   ├── Habilitations
│   ├── Certifications
│   ├── TrainingPlans
│   └── TrainingSessions → TrainingAttendances
│
└── Subscription (Cashier)
    └── Stripe customer + Stripe subscriptions
```

---

*Document maintained alongside the codebase. Update at every phase milestone.*
