# Rapport Frontend — Sprints 1 → 5

**Branche** : `feature/front` (8 commits ahead of `main`)
**Période** : Wave A→E + 5 sprints itératifs
**Statut** : ✅ Prêt pour pilote interne · ⚠️ Pré-pilote externe nécessite Sprint 6

---

## 1. Vue d'ensemble

| Métrique | Valeur |
|---|---|
| Lignes ajoutées (vs `main`) | **+113 460 / -12 769** (net +100 691) |
| Commits feature | 6 (`b2dda30` → `68df64e`) |
| Pages Inertia | **67** (≈ 35 au point de départ) |
| Composants UI primitives | **20** (`components/ui/`) |
| Composants spécialisés | hover-cards · quick-add · preview-sheets · qvct · marketing |
| Controllers Web | **24** (5 nouveaux) |
| Controllers API | **22** (inchangé — parité mobile préservée) |
| Routes web | **147** |
| Modèles Eloquent | **45** (inchangé) |
| Tests Pest | **987 passed**, 5 skipped, 0 failure (2932 assertions, 470 s) |
| Bundle layout | 119 kB → **33,75 kB gzipped** (production) |

### Commits clés

| Hash | Sprint | Lignes |
|---|---|---|
| `b2dda30` | Wave A→E (charts, QVCT, communication, formations, billing) | +6 732 |
| `5776bc5` | Sprint 1 (modals, confirm-dialog, quick-add, MFA banner) | +1 383 |
| `b1cbe92` | Sprint 2 (sheets, wizards MFA/audit, audit-log) | +2 021 |
| `7b83360` | Sprint 3 (wizards care-plan/QVCT-campaign, settings, onboarding) | +1 455 |
| `dbe9caf` | Sprint 4 (filters, bulk actions, hover cards, help drawer) | +1 213 |
| `68df64e` | Sprint 5 (DnD, system banners, bulk endpoint, rich empty states) | +827 |

---

## 2. Détail par Wave/Sprint

### Wave A → E (`b2dda30`) — Backend feature complétude

| Wave | Livré |
|---|---|
| **A** Quick wins | Charts SVG (Line/Bar/Donut/Sparkline) · NotificationsCenter cloche header · CommandPalette Cmd+K cross-domain · Care-plans onboarding · Refonte Indicateurs |
| **B** QVCT M3 | 8 pages (hub, questionnaire anonyme, weak-signals, RPS heatmap, action-plans, journal, exchanges) + 5 composants partagés |
| **C** Communication M4 | 4 onglets (messages, news, documents, Q&A) |
| **D** Formation M5 | 4 onglets (overview, plans annuels, sessions, compétences) |
| **E** Billing | Page abonnement (3 tiers, factures, résiliation, payment method) |

### Sprint 1 P0 (`5776bc5`) — Foundations

- `Modal` (focus trap, ESC, scroll lock, ARIA, 4 tailles)
- `ConfirmDialog` (3 tones · `requireTyped` pour actions irréversibles)
- **15 occurrences de `confirm()` natif remplacées** sur 8 pages
- 4 Quick-add modals : incident · intervention · bénéficiaire · invite user
- `MfaSetupBanner` avec snooze localStorage 24 h

### Sprint 2 P1 (`b1cbe92`) — Sheets & Wizards

- `Sheet` (slide-over) + `Wizard` (multi-step) primitives
- 3 preview-sheets (intervention · incident · bénéficiaire)
- **Wizard Setup MFA** (4 étapes Fortify : intro → QR → verify → backup codes)
- **Wizard Nouvel audit** (4 étapes : référentiel → périmètre → date → récap)
- Page **Registre d'audit** RGPD Art. 30 (filtres event/type/dates + Sheet détail)

### Sprint 3 P1 (`7b83360`) — Wizards business

- **Wizard Care plan** (4 étapes : infos → objectifs → période → récap)
- **Wizard QVCT campaign launch** (4 étapes : questionnaire → audience → période → récap)
- Page **Settings structure** (identité readonly + billing email éditable + 6 cartes conformité)
- **Wizard Onboarding structure** (5 étapes pour 1er login dirigeant)

### Sprint 4 P2 (`dbe9caf`) — Productivité

- `FilterDrawer` + `FilterChipsBar` + saved views localStorage
- `BulkActionsToolbar` + `BulkSelectCheckbox` sur interventions
- `HoverCard` primitive + `UserHoverCard` + `BeneficiaryHoverCard`
- `HelpDrawer` contextuel (8 contextes URL-matchés + cheatsheet raccourcis)

### Sprint 5 P3 (`68df64e`) — Polish

- `useReorderable` + DnD HTML5 + move↑↓ sur tâches plan de soins
- `SystemBanners` (trial-ending, suspended, config-driven via env)
- Backend **bulk-cancel atomique** avec `lockForUpdate` (remplace N×POST)
- `EmptyStateRich` avec 3-card suggestion grid (interventions/bénéficiaires/audits)

---

## 3. Qualité

| Indicateur | État |
|---|---|
| TypeScript strict | 0 nouvelle erreur · 4 préexistantes inchangées (Toast, admin/dashboard, marketing/contact) |
| Build production | 21 s · layout bundle 119 kB → 33,75 kB gzipped |
| Tests Pest | 987 passants, 5 skipped (préexistants), **aucune régression** sur 5 sprints |
| Lefthook | Pint + php-syntax + no-named-individuals → pass sur chaque commit |
| Sécurité | Tenant scoping, encryption at rest, RGPD trail, audit logging respectés sur toutes les nouvelles routes |

---

## 4. Patterns design system établis

| Pattern | Composant | Justification |
|---|---|---|
| Action destructive irréversible | `ConfirmDialog` + `requireTyped` | Empêche les suppressions accidentelles de structures/données (style Stripe/GitHub) |
| Création express depuis liste | Quick-add modal | Réduit la friction d'un clic supplémentaire vers page complète |
| Aperçu sans perdre le contexte | Preview Sheet | Bouton œil sur chaque ligne ouvre un slide-over |
| Multi-étapes complexes | `Wizard` stepper | MFA, audit, care plan, QVCT, onboarding — UX consistent |
| Filtres riches + mémorisation | `FilterDrawer` + saved views | Tournée du matin · Incidents non clos · etc. |
| Sélection multiple | `BulkActionsToolbar` | Toolbar sticky avec actions contextuelles |
| Aperçu contextuel | `HoverCard` (focus-aware) | Mini-profil utilisateur/bénéficiaire |
| Aide contextuelle | `HelpDrawer` URL-aware | Tips par page + cheatsheet raccourcis |
| Annonces transverses | `SystemBanners` | Trial expirant, maintenance, suspension |
| Empty state actionnable | `EmptyStateRich` | 3 cards "Démarrer rapidement" avec tones colorés |

---

## 5. Chantiers restants

### Légende priorité

- **P1** : critique pour pilote externe (4-6 semaines)
- **P2** : produit complet (8-10 semaines)
- **P3** : admin & marketing (10-12 semaines)
- **Phase 3** : Premium (mois 9+)

### Backend riche, frontend partiel

| Domaine | Manque | Priorité |
|---|---|---|
| **Questionnaire builder QVCT** | UI drag-drop questions (modèle existe) | **P1** |
| **Action plans QVCT** | CRUD complet (liste démo seulement) | **P1** |
| **Reverb client** | Branchement Echo + `useEcho` hook (broadcast backend OK) | **P1** |
| **Wayfinder generate** | Régénérer les routes typées (liens hardcodés actuels) | **P1** |
| **Tests Pest manquants** | bulkCancel · reorder · updateContact · audit-log | **P1** (CLAUDE.md exige) |
| **QVCT campaign results** | Graphiques agrégés détaillés | **P2** |
| **Audit grids browse** | Bibliothèque référentiels read-only | **P2** |
| **Audit execution** | Page item-par-item mobile-friendly + autosave | **P2** |
| **HAS preparation** | Guide priorisation visite + classeur de preuves | **P2** |
| **Formation sessions detail** | Émargement + competencies/mine (vue intervenant) | **P2** |
| **News rich-text editor** | TipTap ou Lexical | **P3** |
| **Document uploader** | Drag-drop + progress + EXIF strip | **P3** |
| **Q&A vote + accept** | Actions reliées au backend (UI démo OK) | **P3** |
| **Beneficiary timeline** | Frise unique chronologique | **P3** |

### Pages structures absentes

#### Tier Settings & Profile (P2)

- `dashboard/profile/api-tokens` — gestion Sanctum tokens personnels
- `dashboard/profile/notifications-preferences` — opt-in/out par type/canal
- `dashboard/profile/sessions` — sessions actives + révocation
- `dashboard/settings/teams` — gestion équipes/secteurs
- `dashboard/settings/integrations` — webhooks · Slack/Teams · calendrier

#### Tier Bénéficiaires (P3)

- `beneficiaries/{id}/timeline` — frise unique chronologique (interventions + incidents + plans)
- `beneficiaries/{id}/satisfaction` — historique enquêtes (Phase 3 prep)
- `beneficiaries/{id}/contacts` — famille, médecin, urgences (séparé du dossier médical)

#### Tier Plateforme super-admin (P3)

- `admin/structures/{id}/audit-trail` — cycle de vie structure
- `admin/feature-flags` — pilotage Pennant features par tenant
- `admin/system-health` — métriques infra (queue depth, Reverb connections, jobs failed)

#### Tier Marketing public (P3)

- `/pricing` détaillée
- `/customers` (témoignages)
- `/blog/*` (SEO)
- `/changelog` (release notes publiques)

#### Phase 3 (différé mois 9+)

- **Portail bénéficiaires** (app Inertia séparée ou Next.js PWA)
- **IA prédictive** (burnout risk cards, autonomy loss alerts)
- **Benchmark anonymisé** secteur
- **Tests Playwright E2E** régression

### Améliorations transverses

| Item | Effort | Priorité |
|---|---|---|
| Hover cards sur incidents/beneficiaries/audits lists | 0,5 j | P2 |
| Trigramme PostgreSQL `pg_trgm` pour Cmd+K type-ahead | 1 j | P2 |
| Documentation utilisateur externe (`docs.hsquality.fr`) | 2 sem | P3 |
| Service worker offline pour coordinateurs web | 1 sem | P3 |
| Sélecteur multi-structures (dirigeants multi-tenants) | 3 j | Phase 3 |

---

## 6. Roadmap de priorisation suggérée

### 🥇 Sprint 6 — pré-pilote externe (~2 semaines)

1. **Tests Pest manquants** (bulkCancel, reorder, updateContact, audit-log, mfa-setup) — *exigé CLAUDE.md*
2. **Wayfinder generate** + migration des liens hardcodés vers imports typés
3. **QVCT questionnaire builder** (drag-drop questions) + **action plans CRUD**
4. **Reverb client wired** + démo live messages dans communication

### 🥈 Sprint 7 — productisation (~2-3 semaines)

5. **Audit grids browse** + execution mobile-friendly + autosave
6. **HAS preparation guide** + classeur de preuves
7. **Formations** : sessions detail + émargement + competencies/mine
8. **Profile** : notifications-preferences + API tokens + sessions

### 🥉 Sprint 8 — admin & marketing (~2 semaines)

9. **Admin** : feature flags + system-health + audit-trail structure
10. **Marketing public** : pricing détaillée + customers + changelog
11. **Document uploader** drag-drop + **rich text editor** pour news
12. **Beneficiary timeline** + contacts séparés

### 🎯 Phase 3 — Premium (mois 9+)

13. **Portail bénéficiaires** (app séparée)
14. **IA prédictive** (burnout risk, autonomy loss, predictive cards)
15. **Benchmark anonymisé** secteur
16. **Tests Playwright E2E** régression

---

## 7. Estimation effort total

| Tier | Volume | Effort (1 dev senior) |
|---|---|---|
| Sprint 6 P1 critique | 4 chantiers | 2 semaines |
| Sprint 7 P2 produit | 4 chantiers | 2-3 semaines |
| Sprint 8 P3 admin/marketing | 4 chantiers | 2 semaines |
| Phase 3 (portail/IA/benchmark) | 4 modules | 6-10 semaines |
| **Total restant** | **16 chantiers** | **~12-17 semaines** |

À 2 devs en parallèle : **6-8 semaines** pour atteindre le scope Premium complet.

---

## 8. Liens utiles

- **Implementation plan** : `IMPLEMENTATION_PLAN.txt`
- **Project rules** : `CLAUDE.md`
- **Custom skill** : `qualite-domicile-backend`
- **Security sweep prompt** : `references/security/security-sweep-prompt.md`
- **Screenshots de vérif visuelle** : `wave-verif-*.png`, `sprint*-*.png` (à la racine — à .gitignore-er si non utilisé)

---

## 9. État pour les prochaines étapes

- ✅ **Pilote interne** : prêt (équipe Sophie Martin / DEMO structure)
- ⚠️ **Pilote externe** : à conditionner par Sprint 6 (tests Pest + Wayfinder + QVCT builder + Reverb)
- 🟡 **Production pilot** : Sprint 7 recommandé pour stabiliser audit execution + formations
- 🔴 **GA tous tiers** : Sprint 8 + Phase 3 (~12-17 semaines)

---

*Rapport généré après le commit `68df64e`. Mettre à jour à chaque sprint suivant.*
