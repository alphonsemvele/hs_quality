# QualitéDomicile SaaS — Présentation détaillée du projet

> Document de présentation à jour au 6 mai 2026 · Branche `feature/front` · Suite Pest : **406 tests / 1589 assertions, tous verts**

---

## 1. Objectifs & vision

### 1.1 Le problème
Les structures d'aide et de soins à domicile en France croulent sous la paperasse qualité : audits HAS, plans d'amélioration continue (PAC), traçabilité des interventions, déclarations ARS, dossiers RGPD, suivi des certifications, baromètres QVCT. Tout est fait sur Excel, papier, ou des outils non spécialisés.

### 1.2 La solution
**QualitéDomicile** est un SaaS multi-tenant français spécialisé pour 5 types de structures :

| Acronyme | Type de structure |
|---|---|
| SAAD | Service d'Aide et d'Accompagnement à Domicile |
| SSIAD | Service de Soins Infirmiers à Domicile |
| SPASAD | Service Polyvalent d'Aide et de Soins à Domicile |
| ESAD | Équipe Spécialisée Alzheimer à Domicile |
| CCAS | Centre Communal d'Action Sociale |

Chaque structure abonnée est un **tenant** isolé. Deux interfaces, un seul backend :
- **Web admin** (Inertia + React) — pour les utilisateurs bureau (toujours connectés)
- **Mobile React Native** (offline-first) — pour les intervenants à domicile (zones blanches gérées)

### 1.3 Cibles commerciales
| Phase | Échéance | Cible |
|---|---|---|
| Phase 1 — MVP | Mois 1–4 | 10 structures · 200 intervenants |
| Phase 2 — Pro Qualité | Mois 5–8 | 50 structures · 1 000 intervenants |
| Phase 3 — IA & Data | Mois 9–14 | 200 structures · 5 000–15 000 intervenants |
| Phase 4 — Expansion nationale | Mois 15–24 | 500 structures |
| Phase 5 — Europe | Mois 25–36 | 1 000–3 000 structures (FR + BE + CH + LU) |

### 1.4 Référentiel produit
Le cahier des charges officiel `CDC-QUALITE-DOM-2024-v2.0` (Février 2024) est la source unique de vérité fonctionnelle.

---

## 2. Architecture technique

### 2.1 Stack
| Couche | Technologie | Version |
|---|---|---|
| Backend | Laravel | 12 (PHP 8.3) |
| Web admin | Inertia + React + TypeScript + Tailwind CSS | React 19 / Tailwind v4 |
| Mobile | React Native | offline-first (SQLite) |
| API | REST `/api/v1/*` (Sanctum tokens) + OpenAPI auto-généré (Scramble) | — |
| Base de données | PostgreSQL | 16 |
| Cache / queue / pubsub | Redis | 7 |
| Stockage fichiers | S3 (SSE-KMS) | AWS Paris ou MinIO local |
| WebSocket temps réel | Laravel Reverb | 1 |
| Hébergement production | AWS Paris ou OVHcloud France | **HDS-certifié** |
| Auth | Sessions cookies (web) + Sanctum (mobile) + MFA TOTP (Fortify) | — |

### 2.2 Multi-tenancy
**Modèle row-level** : chaque table métier porte une colonne `structure_id`, et chaque modèle utilise le trait `BelongsToStructure` qui applique automatiquement un global scope. Conséquences :
- Cross-tenant access → **404** (pas 403) — l'enregistrement est invisible
- Test obligatoire sur chaque modèle métier : "fuite cross-tenant"
- Voie d'évasion vers les schémas PostgreSQL préservée pour les tenants entreprise

### 2.3 Logique métier centralisée
Tous les services métier vivent dans `app/Services/*`. **Les contrôleurs Inertia (web) et les contrôleurs API (mobile) appellent les mêmes services** — zéro duplication entre les deux frontends.

---

## 3. Conformité & normes

| Référentiel | Application |
|---|---|
| **RGPD** | Audit logging par tenant, droit à l'effacement (Art. 17), purge des données, consentement explicite |
| **HDS** (Hébergement Données de Santé) | Hébergement obligatoire chez prestataire HDS (AWS Paris / OVHcloud) |
| **ANSSI** | MFA TOTP obligatoire pour les rôles à risque · pas de SMS/email (risque SIM-swap) · CSP, HSTS, secrets en gestionnaire centralisé |
| **CNIL** | Audit log sur chaque accès au dossier bénéficiaire (`log_sensitive_read`) |
| **Référentiel HAS** | Module Audits (M6), PAC (Plan d'Amélioration Continue), préparation HAS |

---

## 4. Sécurité

La sécurité est priorité n°1 et fait partie intégrante du design dès le jour 1, **jamais** "ajoutée plus tard".

### 4.1 Ordre de contrôle systématique sur chaque écriture
1. **Tenant scope** — global scope `BelongsToStructure` filtre déjà
2. **Policy** — `BasePolicy` vérifie d'abord l'appartenance au tenant
3. **Form Request** — `BaseFormRequest::authorize()` re-confirme côté HTTP
4. **Audit log** — entrée d'audit obligatoire pour toute action métier sensible

### 4.2 Authentification & MFA
- **Sessions cookies + CSRF** côté web (Fortify)
- **Tokens Sanctum** côté mobile (révocation, scopes)
- **MFA TOTP obligatoire** pour : dirigeants, coordinateurs, référents qualité, RH
- **MFA optionnel** pour intervenants (UX terrain — biométrie de l'appareil sécurise déjà le token mobile)
- **SMS et email refusés** (risque SIM-swap, recommandation NIST/ANSSI/CNIL pour données de santé)

### 4.3 Durcissement HTTP
- **CSP, HSTS, X-Frame-Options, X-Content-Type-Options** appliqués globalement (middleware `SecurityHeaders`)
- **Rate limiting** finement segmenté :
  - `api` : 60/min · `login` : 5/min · `two-factor` : 5/min
  - `incident-declare` : 30/min · `sync` : 20/min · `mobile-api` : 300/min
- **Idempotency-Key** sur toutes les écritures API mobile (rejeu sûr 24h)

### 4.4 Données & médias
- **Strip EXIF** automatique sur photos d'intervention (`InterventionMediaService`) — anti-fuite GPS/identité
- **S3 SSE-KMS** : chiffrement au repos
- **URLs signées** pour récupération média (TTL court)
- **Pas de `{!! !!}` Blade ni `dangerouslyHTML` React** sur input utilisateur

### 4.5 Audit & traçabilité
- `owen-it/laravel-auditing` sur tous les modèles touchant données de santé/QVCT
- Middleware `log_sensitive_read` sur `/beneficiaries/{id}/dossier` (CDC §5.2)
- **Tenant-aware audit** — pas de fuite entre structures

### 4.6 Garde-fous opérationnels
- **`AppServiceProvider::guardProductionDebug()`** : abort si `APP_ENV=production && APP_DEBUG=true`
- **Pre-commit hooks lefthook** : Pint + syntaxe PHP + détection de noms en dur + audit Composer + suite Pest
- **Tests cross-tenant obligatoires** sur chaque nouveau modèle métier
- **Secrets** uniquement via gestionnaire de plateforme (jamais `.env` copié à la main)

---

## 5. Rôles & permissions

### 5.1 Six personas

| Rôle | Description | MFA |
|---|---|---|
| `intervenant` | Aide à domicile, terrain, mobile-first | optionnel (biométrie) |
| `coordinateur` | Planifie et supervise les interventions | **obligatoire** |
| `dirigeant` | Direction de la structure | **obligatoire** |
| `referent_qualite` | Référent qualité, audits, PAC, HAS | **obligatoire** |
| `rh` | Ressources humaines, formations, QVCT | **obligatoire** |
| `beneficiaire_portal` | Portail bénéficiaire (Phase 2) | optionnel |

Plus un compte plateforme spécial : `super_admin` (gestion des structures, jamais de données métier).

### 5.2 Matrice fonctionnelle (extrait)

| Action | Intervenant | Coordinateur | Dirigeant | Référent Q. | RH |
|---|:---:|:---:|:---:|:---:|:---:|
| Voir ses interventions | ✓ (siennes) | ✓ (structure) | ✓ (structure) | ✓ (structure) | — |
| Planifier intervention | — | ✓ | ✓ | — | — |
| Check-in / Check-out | ✓ (siennes) | ✓ | ✓ | — | — |
| Voir bénéficiaires | ✓ (assignés) | ✓ (structure) | ✓ (structure) | ✓ (structure) | — |
| Créer / éditer bénéficiaire | — | ✓ | ✓ | — | — |
| Déclarer incident | ✓ | ✓ | ✓ | — | — |
| Analyser incident | — | ✓ | ✓ | ✓ | — |
| Clôturer incident | — | ✓ | ✓ | ✓ | — |
| Notifier ARS | — | ✓ | ✓ | ✓ | — |
| Audits & conformité | — | (lecture) | ✓ | ✓ | — |
| Plans d'amélioration | — | ✓ (PAC) | ✓ | ✓ | — |
| Indicateurs / dashboard exec | — | (opérationnel) | ✓ | ✓ | — |
| Baromètre QVCT | (répond) | (lecture) | ✓ | (répond) | ✓ |
| Formations | — | (équipe) | ✓ | — | ✓ |
| Communication / messages | (envoie) | ✓ (modère) | ✓ | (envoie) | ✓ (modère) |
| Gestion utilisateurs | — | — | ✓ | — | ✓ |
| Effacement RGPD (exécuter) | — | — | ✓ | — | — |

### 5.3 Implémentation technique
- **Spatie laravel-permission** avec `team_foreign_key = structure_id` (permissions scopées au tenant)
- **~70 permissions atomiques** dans `RoleSeeder.php` (ex : `incidents.declare`, `interventions.update.team`, `pac.generate`)
- **Une `Policy` par modèle** étendant `BasePolicy` — vérifie tenant avant tout
- **Matrice frontend (`UserAbilities`)** alignée avec le backend — partagée à React via Inertia, alimente le `useCan()` qui cache/montre les boutons et les menus de navigation

### 5.4 Cohérence frontend ↔ backend
**Garantie par tests** : `RoleSeederTest`, `UserAbilitiesTest`, `SharedAbilitiesTest`. Chaque divergence détectée fait échouer la CI. Audit récent : 4 incohérences corrigées (`referent_qualite` ne déclare plus d'incident en UI · `dirigeant` reçoit `interventions.update.team`, `interventions.delete`, `incidents.declare`).

---

## 6. Modules & pages

### 6.1 État d'avancement par module

| # | Module | Backend | Frontend Web | Mobile API | Tests | État |
|---|---|---|---|---|---|---|
| Phase 0 | Fondations (tenancy, RBAC, audit, sécurité, CI/CD, Terraform) | ✓ | — | — | ✓ | **Terminé** |
| M1 | Bénéficiaires (dossier, données) | ✓ | ✓ | ✓ (lecture) | ✓ | **Terminé** |
| M1 | Plans de soins (PAC) + tâches planifiées | ✓ | ✓ | — | ✓ | **Terminé** |
| M1 | Affectations intervenants | ✓ | ✓ | — | ✓ | **Terminé** |
| M2 | Interventions (machine à états + GPS) | ✓ | ✓ | ✓ | ✓ | **Terminé** |
| M2 | Photos S3 (strip EXIF) + signatures | ✓ | ✓ | ✓ | ✓ | **Terminé** |
| M2 | Événements Reverb temps réel + dashboard stats | ✓ | ✓ | — | ✓ | **Terminé** |
| M3 | Incidents + classification gravité auto + ARS | ✓ | ✓ | ✓ | ✓ | **Terminé** |
| M3 | Cache Redis tag-flush sur dashboard | ✓ | ✓ | — | ✓ | **Terminé** |
| M4 W1 | API mobile `/api/v1/*` + Sanctum + idempotence + sync batch | ✓ | — | ✓ | ✓ | **Terminé** |
| M4 W2 | App React Native squelette | — | — | — | — | À venir |
| M4 W3 | Durcissement protocole sync offline | — | — | — | — | À venir |
| M4 W4 | Onboarding pilote | — | — | — | — | À venir |
| M5+ | Audits & conformité (HAS) | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| M6+ | Plans d'amélioration | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| M7+ | Indicateurs (dashboard exec) | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| M8+ | Baromètre QVCT (questionnaire + alertes) | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| M9+ | Formations & certifications | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| M10+ | Communication (messagerie + actualités) | partiel | ✓ (UI demo) | — | — | En cours (squelette) |
| Admin | Gestion structures (super_admin) + invitations users | ✓ | ✓ | — | ✓ | **Terminé** |

### 6.2 Pages web disponibles (Inertia)

**Authentification** (`/login`, `/register`, `/forgot-password`, MFA TOTP setup/challenge)

**Dashboard** (`/dashboard`)
- KPI : interventions du mois, incidents déclarés, score conformité, complétion PAC, score QVCT moyen
- Liste des incidents récents avec gravité
- Alertes QVCT
- Actions rapides (déclarer incident, etc.) — affichées **selon les abilities du persona**
- Pagination & filtres
- Mode sombre / clair

**Modules opérationnels (terrain)**
- `/interventions` — liste + create + show + edit (CRUD complet) + check-in/out/cancel + photos + signatures
- `/incidents` — liste + create (déclaration) + show (avec actions correctives, suivi) + transitions de statut (assigner, analyser, clôturer)
- `/beneficiaries` — liste + create + show + edit + **dossier médical** (audit-loggé)
- `/beneficiaries/{id}/care-plans` — liste + create + show + edit + activate/archive/copy

**Modules qualité** (UI prête, backend en cours)
- `/audits` — liste + create + show
- `/plans-amelioration` — liste + create + show
- `/indicateurs` — dashboard

**QVCT & RH** (UI prête, backend en cours)
- `/qvct` — accueil + questionnaire
- `/formations` — liste
- `/communication` — messagerie/actualités

**Administration**
- `/users` — gestion utilisateurs intra-tenant (invitation, désactivation)
- `/admin/structures` — gestion des tenants (super_admin uniquement)
- `/dashboard/profile` — profil personnel + 2FA

**Documentation API**
- `/docs/api` — OpenAPI auto-généré (Scramble), accès Bearer

### 6.3 API mobile `/api/v1/*` — endpoints disponibles

| Méthode | Route | Description |
|---|---|---|
| `POST` | `/auth/login` | Émission token Sanctum (5/min) |
| `POST` | `/auth/logout` | Révocation token courant |
| `GET` | `/auth/me` | Profil utilisateur courant |
| `GET` | `/interventions[?date=…]` | Liste paginée 50, intervenant ne voit que les siennes |
| `GET` | `/interventions/{id}` | Détail avec bénéficiaire imbriqué |
| `POST` | `/interventions/{id}/check-in` | Idempotent · `{lat, lng}` |
| `POST` | `/interventions/{id}/check-out` | Idempotent · `{report_text}` |
| `POST` | `/interventions/{id}/cancel` | Idempotent · `{reason}` |
| `POST DELETE` | `/interventions/{id}/photos[/{photo}]` | Multipart, idempotent |
| `POST` | `/interventions/{id}/signatures` | Base64 PNG, idempotent |
| `GET POST` | `/incidents` | Liste + déclaration (gravité auto + dispatch ARS) |
| `GET` | `/incidents/{id}` | Détail |
| `GET` | `/beneficiaries[/{id}]` | Liste paginée 100 + détail |
| `POST` | `/sync/batch` | Synchronisation offline batch |

**Garanties API** : Sanctum tokens · throttle `mobile-api` 300 req/min · `Idempotency-Key` rejoue 2xx pendant 24h · OpenAPI auto-généré.

---

## 7. Qualité & tests

### 7.1 Couverture
- **Pest exclusivement** (pas de PHPUnit)
- **64 fichiers de tests** : Feature + Unit
- **406 tests · 1 589 assertions, 100 % verts**
- Suite à jour à chaque PR (pre-push hook)

### 7.2 Catégories de tests
| Catégorie | Exemples |
|---|---|
| Domain | `BeneficiaryHttpTest`, `InterventionEventsTest`, `IncidentHttpTest` |
| **Tenancy** | `BeneficiaryTenantIsolationTest`, `IntervenantAssignmentTenantIsolationTest`, `StructureScopeTest` (cross-tenant leak — obligatoire par modèle) |
| Auth & MFA | `RequireMfaTest`, `MobileMfaGateTest`, `LoginRateLimitTest` |
| RBAC | `RoleSeederTest`, `TenantScopedRolesTest`, `UserAbilitiesTest`, `SharedAbilitiesTest` |
| Sécurité | `SecurityHeadersTest`, `CspProductionTest`, `PasswordEndpointThrottleTest`, `InertiaUserShareTest` |
| Audit | `TenantAwareAuditTest`, `HealthDataAuditCoverageTest` |
| API | `BeneficiaryApiTest`, `IncidentApiTest`, `InterventionApiTest`, `SyncBatchTest`, `IncidentClientUuidTest` |
| Services (unit) | `InterventionServiceTest`, `IncidentServiceTest`, `GraviteClassifierTest`, `DashboardStatsServiceTest` |

### 7.3 Outillage CI/CD
- **Lefthook** pre-commit : Pint + syntaxe PHP + détection nominative en dur
- **Lefthook** pre-push : `composer audit` + suite Pest entière
- **GitHub Actions** : type-check, lint, tests, security audit
- **Sentry** monitoring (DSN d'évaluation pour l'instant)
- **Health endpoints** : `/health/live` (ALB) et `/health/ready` (DB + Redis + S3)
- **Terraform** scaffold AWS Paris (data plane)

---

## 8. Roadmap

### 8.1 Phase 1 — MVP (en cours, ~M4 W1 atteint)
- [x] M1 : Bénéficiaires + Plans de soins + Affectations
- [x] M2 : Interventions + médias + temps réel
- [x] M3 : Incidents + ARS + dashboard
- [x] M4 W1 : API mobile + Sanctum + sync batch
- [ ] M4 W2 : App React Native (mobile team)
- [ ] M4 W3 : Durcissement sync offline
- [ ] M4 W4 : Pilote 10 structures

### 8.2 Phase 2 — Pro Qualité (Mois 5–8)
- Audits HAS complets (formulaires guidés, scoring)
- PAC (plans d'amélioration continue) — workflow plein
- Indicateurs réglementaires + rapports annuels
- Baromètre QVCT complet (questionnaire + alertes auto + plan d'actions)
- Module formations + certifications
- Module communication (messagerie + actualités modérées)

### 8.3 Phase 3 — IA & Premium (Mois 9–14)
- Détection précoce risque burnout (modèle prédictif sur QVCT)
- Détection perte d'autonomie bénéficiaire (signaux comportementaux)
- Portail bénéficiaire (consultation plan, satisfaction, déclaration incident)
- Cross-tenant benchmark anonymisé

### 8.4 Phase 4 — Expansion nationale (Mois 15–24)
- 500 structures
- Intégrations partenaires (CPAM, Conseil départemental, ARS)

### 8.5 Phase 5 — Europe (Mois 25–36)
- 1 000–3 000 structures FR + BE + CH + LU
- i18n (NL, DE) — préparée dès le départ via `lang/fr/*.php`

---

## 9. Métriques clés à présenter

| Métrique | Valeur |
|---|---|
| Commits | 37 |
| Modèles métier | 13 |
| Contrôleurs web | 17 |
| Contrôleurs API | 5 |
| Services métier | 13 |
| Pages Inertia | ~40 |
| Permissions atomiques | ~70 |
| Personas | 6 |
| Tests Pest | 406 |
| Assertions | 1 589 |
| Couverture tenancy | 100 % des modèles métier |
| Phase 1 avancement | ~85 % (M4 W1/W4 atteint) |

---

## 10. Points différenciants à marteler

1. **Sécurité by-design** — pas un bolt-on : isolation tenant testée à chaque PR, MFA fort, audit complet, conformité RGPD/HDS dès le commit 1
2. **Une base, deux frontends** — la logique métier vit dans `app/Services/*`, zéro duplication web/mobile, garantie par les tests
3. **Offline-first nativement** — l'API mobile gère idempotence et `/sync/batch` pour les zones blanches (réalité du métier)
4. **Conformité française** — HDS, ANSSI, RGPD, HAS — pas un produit US francisé
5. **Suite de tests verte en permanence** — 406 tests à chaque push, gage de stabilité pour la production
6. **Cohérence RBAC frontend ↔ backend prouvée** — la matrice de permissions est testée des deux côtés (la régression "bouton qui 403" est impossible en silence)

---

## 11. Plan de démo live (10 min)

| # | Action | Persona | Ce que ça démontre |
|---|---|---|---|
| 1 | Login `dirigeant@demo.fr` → dashboard | Dirigeant | KPI, alertes, actions conditionnelles |
| 2 | Toggle dark / mobile responsive | — | UX moderne |
| 3 | Bénéficiaires → ouvrir un dossier | Dirigeant | **Audit log déclenché** (montrer l'entrée auditing dans la BDD) |
| 4 | Créer une intervention → check-in (GPS) → photo → check-out → signature | Coordinateur | Cycle complet métier |
| 5 | Déclarer un incident grave | Intervenant | Classification gravité auto + dispatch ARS |
| 6 | Se reconnecter en `qualite@demo.fr` | Référent qualité | **Le bouton "Déclarer un incident" disparaît** — RBAC en action |
| 7 | Ouvrir `/docs/api` | — | API mobile prête, OpenAPI |
| 8 | Tenter cross-tenant (URL forgée) | n'importe lequel | **404** — preuve de l'isolation |

---

## 12. Comptes de démonstration

Tous les comptes ont le mot de passe `password` (environnement dev uniquement).

| Email | Rôle | Usage démo |
|---|---|---|
| `dirigeant@demo.fr` | Dirigeant | Vue exécutive complète |
| `coordinateur@demo.fr` | Coordinateur | Planification + cycle complet intervention |
| `qualite@demo.fr` | Référent qualité | Audits, PAC, analyse incidents |
| `intervenant@demo.fr` | Intervenant | Vue terrain, déclaration incident |
| `intervenant2@demo.fr` | Intervenant | Second compte pour assignations multiples |
| `rh@demo.fr` | RH | QVCT, formations, gestion équipe |

---

**En une phrase pour la conclusion** : *« On a livré 85 % du MVP en 4 mois, avec un niveau de sécurité, de conformité et de qualité testée que peu de SaaS atteignent à ce stade — et on est à 4 semaines du pilote terrain. »*
