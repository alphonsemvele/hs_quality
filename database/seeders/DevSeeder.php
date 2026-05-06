<?php

namespace Database\Seeders;

use App\Enums\AuditStatus;
use App\Enums\CarePlanStatus;
use App\Enums\CategorieIncident;
use App\Enums\EcartGravite;
use App\Enums\GraviteIncident;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Enums\Referentiel;
use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Enums\UserType;
use App\Models\ActionAmelioration;
use App\Models\AuditEcart;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Incident;
use App\Models\IncidentActionCorrective;
use App\Models\IncidentSuivi;
use App\Models\IntervenantAssignment;
use App\Models\Intervention;
use App\Models\PlanAmelioration;
use App\Models\PlannedTask;
use App\Models\QualityAudit;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dev seeder — creates a known structure, one user per role, and rich
 * sample data across all implemented modules so the dashboard and every
 * list/show page has content.
 *
 * Run: php artisan db:seed --class=DevSeeder
 *      (or include in DatabaseSeeder for `php artisan migrate:fresh --seed`)
 *
 * Default password for every account: password
 */
class DevSeeder extends Seeder
{
    public function run(): void
    {
        // RoleSeeder must run first so permissions exist.
        $this->call(RoleSeeder::class);

        // ── Platform admin (no tenant — accesses /admin/* surface) ───────────
        // Idempotent. Lives outside any tenant — TenantResolver lets through
        // is_platform_admin=true without binding a structure context.
        User::firstWhere('email', 'admin@platform.fr')
            ?? User::factory()->create([
                'first_name' => 'Platform',
                'last_name' => 'Admin',
                'email' => 'admin@platform.fr',
                'password' => Hash::make('password'),
                'structure_id' => null,
                'is_platform_admin' => true,
                'type' => null,
            ]);

        $structure = Structure::firstWhere('code', 'DEMO')
            ?? Structure::factory()->create([
                'name' => 'Structure Demo',
                'code' => 'DEMO',
            ]);

        // Bind tenant context so the global scope + Spatie team_id work.
        app()->instance('current_structure', $structure);
        app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());

        // ── Users ────────────────────────────────────────────────────────────
        $accounts = [
            ['type' => UserType::Dirigeant, 'role' => 'dirigeant', 'email' => 'dirigeant@demo.fr', 'first' => 'Sophie', 'last' => 'Martin'],
            ['type' => UserType::Coordinateur, 'role' => 'coordinateur', 'email' => 'coordinateur@demo.fr', 'first' => 'Thomas', 'last' => 'Dupont'],
            ['type' => UserType::ReferentQualite, 'role' => 'referent_qualite', 'email' => 'qualite@demo.fr', 'first' => 'Claire', 'last' => 'Bernard'],
            ['type' => UserType::Intervenant, 'role' => 'intervenant', 'email' => 'intervenant@demo.fr', 'first' => 'Marie', 'last' => 'Leclerc'],
        ];

        $users = [];
        foreach ($accounts as $account) {
            $user = User::firstWhere('email', $account['email'])
                ?? User::factory()
                    ->forStructure($structure)
                    ->state([
                        'first_name' => $account['first'],
                        'last_name' => $account['last'],
                        'email' => $account['email'],
                        'password' => Hash::make('password'),
                        'type' => $account['type']->value,
                    ])
                    ->create();

            if (! $user->hasRole($account['role'])) {
                $user->assignRole($account['role']);
            }

            $users[$account['role']] = $user;
        }

        // Create a second intervenant for variety.
        $intervenant2 = User::firstWhere('email', 'intervenant2@demo.fr')
            ?? User::factory()
                ->forStructure($structure)
                ->state([
                    'first_name' => 'Luc',
                    'last_name' => 'Moreau',
                    'email' => 'intervenant2@demo.fr',
                    'password' => Hash::make('password'),
                    'type' => UserType::Intervenant->value,
                ])
                ->create();
        if (! $intervenant2->hasRole('intervenant')) {
            $intervenant2->assignRole('intervenant');
        }

        // Create an RH user.
        $rh = User::firstWhere('email', 'rh@demo.fr')
            ?? User::factory()
                ->forStructure($structure)
                ->state([
                    'first_name' => 'Anne',
                    'last_name' => 'Petit',
                    'email' => 'rh@demo.fr',
                    'password' => Hash::make('password'),
                    'type' => UserType::Rh->value,
                ])
                ->create();
        if (! $rh->hasRole('rh')) {
            $rh->assignRole('rh');
        }

        // Skip if already seeded (idempotent). Secondary structures are
        // still seeded below — they're idempotent on their own and the
        // platform admin dashboard needs them present.
        if (Beneficiary::where('structure_id', $structure->id)->count() >= 5) {
            $this->command->info('  Demo data already exists — skipping main structure.');
            $this->seedSecondaryStructures();
            $this->printAccounts($accounts);

            return;
        }

        $this->command->info('  Seeding demo data…');

        // ── Beneficiaries ────────────────────────────────────────────────────
        $beneficiaries = Beneficiary::factory()
            ->forStructure($structure)
            ->count(8)
            ->create();

        // ── Assignments ──────────────────────────────────────────────────────
        $intervenant1 = $users['intervenant'];

        foreach ($beneficiaries as $index => $beneficiary) {
            $intervenant = $index % 2 === 0 ? $intervenant1 : $intervenant2;
            IntervenantAssignment::factory()
                ->between($intervenant, $beneficiary)
                ->create();
        }

        // ── Interventions (varied statuses) ──────────────────────────────────
        foreach ($beneficiaries as $index => $beneficiary) {
            $intervenant = $index % 2 === 0 ? $intervenant1 : $intervenant2;

            // 2 completed, 1 planned, 1 in-progress for first 4 beneficiaries
            Intervention::factory()
                ->count(2)
                ->completed()
                ->forBeneficiary($beneficiary)
                ->forIntervenant($intervenant)
                ->create();

            Intervention::factory()
                ->planned()
                ->forBeneficiary($beneficiary)
                ->forIntervenant($intervenant)
                ->create();

            if ($index < 4) {
                Intervention::factory()
                    ->inProgress()
                    ->forBeneficiary($beneficiary)
                    ->forIntervenant($intervenant)
                    ->create();
            }

            if ($index === 5) {
                Intervention::factory()
                    ->cancelled()
                    ->forBeneficiary($beneficiary)
                    ->forIntervenant($intervenant)
                    ->create();
            }
        }

        // ── Care Plans + Planned Tasks ───────────────────────────────────────
        foreach ($beneficiaries->take(5) as $index => $beneficiary) {
            $status = match ($index) {
                0, 1 => CarePlanStatus::Active,
                2 => CarePlanStatus::Draft,
                3 => CarePlanStatus::Archived,
                default => CarePlanStatus::Active,
            };

            $plan = CarePlan::factory()
                ->forBeneficiary($beneficiary)
                ->state([
                    'created_by_user_id' => $users['coordinateur']->id,
                    'status' => $status->value,
                    'archived_at' => $status === CarePlanStatus::Archived ? now()->subDays(30) : null,
                    'archived_reason' => $status === CarePlanStatus::Archived ? 'Plan remplacé par une nouvelle version' : null,
                ])
                ->create();

            $taskTitles = [
                'Toilette et habillage',
                'Préparation du petit-déjeuner',
                'Prise des médicaments',
                'Aide à la mobilité',
                'Entretien courant du logement',
            ];

            foreach (array_slice($taskTitles, 0, rand(3, 5)) as $order => $title) {
                PlannedTask::factory()
                    ->forCarePlan($plan)
                    ->order($order + 1)
                    ->state([
                        'title' => $title,
                        'mandatory' => $order < 3,
                    ])
                    ->create();
            }
        }

        // ── Incidents (varied gravities & statuses) ──────────────────────────
        $coordinateur = $users['coordinateur'];
        $qualite = $users['referent_qualite'];

        // 1. Incident mineur, clos
        $incidentClos = Incident::factory()
            ->forStructure($structure)
            ->declaredBy($intervenant1)
            ->clos()
            ->state([
                'categorie' => CategorieIncident::Chute->value,
                'description' => 'Mme D. a glissé dans la salle de bain en sortant de la douche. Pas de blessure constatée, légère frayeur. Sol mouillé, absence de tapis antidérapant.',
                'lieu' => 'Domicile bénéficiaire, salle de bain',
                'analyse_causes' => "Pourquoi 1 : La bénéficiaire a glissé → sol mouillé après la douche.\nPourquoi 2 : Le sol était mouillé → absence de tapis antidérapant.\nPourquoi 3 : Pas de tapis → non prévu dans l'évaluation initiale du domicile.\nPourquoi 4 : Évaluation incomplète → check-list domicile pas à jour.\nPourquoi 5 : Check-list obsolète → pas de révision depuis 18 mois.\n\nCause racine : Absence de processus de révision périodique des évaluations domicile.",
            ])
            ->create();

        IncidentActionCorrective::factory()
            ->forIncident($incidentClos)
            ->withResponsable($coordinateur)
            ->done()
            ->state(['description' => 'Installer un tapis antidérapant chez Mme D.'])
            ->create();

        IncidentActionCorrective::factory()
            ->forIncident($incidentClos)
            ->withResponsable($qualite)
            ->done()
            ->state(['description' => 'Mettre à jour la check-list d\'évaluation domicile'])
            ->create();

        IncidentSuivi::factory()
            ->forIncident($incidentClos)
            ->by($coordinateur)
            ->state(['note' => 'Tapis antidérapant posé le lendemain de la déclaration. Bénéficiaire rassurée.'])
            ->create();

        IncidentSuivi::factory()
            ->forIncident($incidentClos)
            ->by($qualite)
            ->state(['note' => 'Check-list domicile révisée et diffusée à toute l\'équipe. Audit terrain planifié dans 2 semaines.'])
            ->create();

        // 2. Incident significatif, en analyse
        $incidentAnalyse = Incident::factory()
            ->forStructure($structure)
            ->declaredBy($intervenant2)
            ->enAnalyse()
            ->state([
                'categorie' => CategorieIncident::ErreurMedicamenteuse->value,
                'gravite' => GraviteIncident::Significatif->value,
                'description' => 'Erreur dans la préparation du pilulier : dosage du matin inversé avec celui du soir pour M. R. Erreur détectée par l\'intervenant suivant avant administration.',
                'lieu' => 'Domicile bénéficiaire',
                'assigned_to' => $qualite->id,
            ])
            ->create();

        IncidentSuivi::factory()
            ->forIncident($incidentAnalyse)
            ->by($qualite)
            ->state(['note' => 'Analyse en cours. Double vérification du protocole médicamenteux demandée à l\'ensemble de l\'équipe.'])
            ->create();

        // 3. Incident grave, déclaré (récent)
        Incident::factory()
            ->forStructure($structure)
            ->declaredBy($intervenant1)
            ->grave()
            ->state([
                'categorie' => CategorieIncident::Chute->value,
                'description' => 'Chute de M. B. dans les escaliers lors de l\'accompagnement aux courses. Fracture du poignet constatée aux urgences. Transport par les pompiers.',
                'lieu' => 'Escalier extérieur du domicile',
                'avec_blessure_physique' => true,
                'avec_hospitalisation' => true,
            ])
            ->create();

        // 4. Incident critique
        $incidentCritique = Incident::factory()
            ->forStructure($structure)
            ->declaredBy($coordinateur)
            ->critique()
            ->planActions()
            ->state([
                'description' => 'Situation de danger détectée : bénéficiaire retrouvée désorientée à l\'extérieur du domicile en pleine nuit par un voisin. Contexte de troubles cognitifs avancés non stabilisés.',
                'lieu' => 'Extérieur du domicile',
                'assigned_to' => $qualite->id,
                'analyse_causes' => "Pourquoi 1 : Bénéficiaire sortie de nuit → déambulation liée aux troubles cognitifs.\nPourquoi 2 : Pas de surveillance nocturne → plan d'accompagnement ne prévoit que des passages diurnes.\nPourquoi 3 : Évaluation GIR sous-estimée → dernière réévaluation il y a 8 mois.\nPourquoi 4 : Pas de signal d'alerte → pas de dispositif de téléassistance en place.\nPourquoi 5 : Téléassistance non proposée → absence de protocole systématique.\n\nCause racine : Absence de réévaluation régulière du GIR et de protocole de préconisation téléassistance.",
            ])
            ->create();

        IncidentActionCorrective::factory()
            ->forIncident($incidentCritique)
            ->withResponsable($coordinateur)
            ->state([
                'description' => 'Réévaluer le GIR de la bénéficiaire en urgence et adapter le plan',
                'echeance' => now()->addDays(3)->format('Y-m-d'),
                'statut' => 'en_cours',
            ])
            ->create();

        IncidentActionCorrective::factory()
            ->forIncident($incidentCritique)
            ->withResponsable($qualite)
            ->state([
                'description' => 'Mettre en place un protocole systématique de préconisation téléassistance pour les bénéficiaires GIR 1-3',
                'echeance' => now()->addDays(14)->format('Y-m-d'),
                'statut' => 'planifiee',
            ])
            ->create();

        IncidentActionCorrective::factory()
            ->forIncident($incidentCritique)
            ->withResponsable($coordinateur)
            ->state([
                'description' => 'Installer un dispositif de téléassistance au domicile',
                'echeance' => now()->addDays(5)->format('Y-m-d'),
                'statut' => 'en_cours',
            ])
            ->create();

        IncidentSuivi::factory()
            ->forIncident($incidentCritique)
            ->by($coordinateur)
            ->state(['note' => 'ARS notifiée dans les 24h conformément au protocole. Famille informée. Passage renforcé mis en place en attendant la réévaluation GIR.'])
            ->create();

        // 5. A few more minor incidents for volume
        Incident::factory()
            ->count(3)
            ->forStructure($structure)
            ->declaredBy($intervenant2)
            ->state(['categorie' => CategorieIncident::Autre->value])
            ->create();

        // ── Quality Audits + PAC (Module 6) ──────────────────────────────────
        $dirigeant = $users['dirigeant'];

        // Audit terminé HAS — score 78 avec 3 écarts
        $auditTermine = QualityAudit::factory()
            ->forStructure($structure)
            ->createdBy($qualite)
            ->termine(78)
            ->state([
                'titre' => 'Audit HAS — Évaluation externe annuelle',
                'referentiel' => Referentiel::Has->value,
                'description' => "Évaluation externe annuelle couvrant les 5 domaines du référentiel HAS :\n- Droits des usagers\n- Personnalisation de l'accompagnement\n- Organisation interne\n- Prévention des risques\n- Amélioration continue",
                'date_audit' => now()->subMonths(2)->format('Y-m-d'),
                'auditeur' => 'Dr. Lefèvre (cabinet AQS)',
            ])
            ->create();
        AuditEcart::factory()->forAudit($auditTermine)->majeur()->create([
            'critere' => 'Traçabilité des transmissions',
            'constat' => 'Transmissions orales non systématiquement tracées dans le cahier numérique.',
            'action_corrective' => 'Mise en place du cahier numérique obligatoire',
        ]);
        AuditEcart::factory()->forAudit($auditTermine)->create([
            'critere' => 'Plan de formation',
            'constat' => 'Absence de plan de formation formalisé pour 2026.',
            'gravite' => EcartGravite::Mineur->value,
        ]);
        AuditEcart::factory()->forAudit($auditTermine)->majeur()->create([
            'critere' => 'Protocole médicamenteux',
            'constat' => 'Protocole non mis à jour depuis 14 mois.',
        ]);

        // Audit en cours — interne sur la prévention des chutes
        $auditEnCours = QualityAudit::factory()
            ->forStructure($structure)
            ->createdBy($qualite)
            ->enCours()
            ->state([
                'titre' => 'Audit interne — Prévention des chutes',
                'referentiel' => Referentiel::Interne->value,
                'description' => "Audit ciblé sur les pratiques de prévention des chutes à domicile suite à la série d'incidents Q1.",
                'date_audit' => now()->subDays(3)->format('Y-m-d'),
                'auditeur' => $qualite->fullName(),
            ])
            ->create();
        AuditEcart::factory()->forAudit($auditEnCours)->critique()->create([
            'critere' => 'Évaluation domicile',
            'constat' => 'Aucune check-list d\'évaluation des risques de chute dans 4 dossiers sur 8.',
        ]);

        // Audit planifié — pré-audit ISO 9001
        QualityAudit::factory()
            ->forStructure($structure)
            ->createdBy($dirigeant)
            ->state([
                'titre' => 'Audit ISO 9001 — Pré-audit de certification',
                'referentiel' => Referentiel::Iso9001->value,
                'description' => 'Pré-audit en vue de la certification ISO 9001:2015 prévue pour le second semestre.',
                'date_audit' => now()->addMonths(2)->format('Y-m-d'),
                'statut' => AuditStatus::Planifie->value,
            ])
            ->create();

        // PAC issu de l'audit terminé — en cours, 2 actions sur 3 réalisées
        $pacAudit = PlanAmelioration::factory()
            ->forStructure($structure)
            ->createdBy($qualite)
            ->fromAudit($auditTermine->id)
            ->enCours()
            ->state([
                'titre' => 'Mise à jour du protocole médicamenteux',
                'constat' => 'Protocole non mis à jour depuis 14 mois — risque sur la traçabilité de l\'administration.',
                'responsable' => 'Claire Bernard',
                'echeance' => now()->addWeeks(3)->format('Y-m-d'),
            ])
            ->create();
        ActionAmelioration::factory()->forPlan($pacAudit)->realisee()->create([
            'description' => 'Audit du protocole existant — diagnostic des écarts.',
            'responsable' => 'Claire Bernard',
        ]);
        ActionAmelioration::factory()->forPlan($pacAudit)->realisee()->create([
            'description' => 'Rédaction du protocole v2 (revue par le médecin coordonnateur).',
            'responsable' => 'Claire Bernard',
        ]);
        ActionAmelioration::factory()->forPlan($pacAudit)->enCours()->create([
            'description' => 'Formation des intervenants au nouveau protocole — session terrain.',
            'responsable' => 'Anne Petit',
            'echeance' => now()->addWeeks(2)->format('Y-m-d'),
        ]);

        // PAC ouvert depuis un incident QVCT
        PlanAmelioration::factory()
            ->forStructure($structure)
            ->createdBy($dirigeant)
            ->state([
                'titre' => 'Plan QVCT — réduction du turnover',
                'source' => PacSource::Qvct->value,
                'constat' => 'Score QVCT moyen 5.4/10 sur le secteur Sud, signaux de surcharge identifiés.',
                'responsable' => 'Anne Petit',
                'echeance' => now()->addMonths(2)->format('Y-m-d'),
                'statut' => PlanAmeliorationStatus::Ouvert->value,
            ])
            ->create();

        // PAC terminé — exemple d'historique
        $pacTermine = PlanAmelioration::factory()
            ->forStructure($structure)
            ->createdBy($qualite)
            ->termine()
            ->state([
                'titre' => 'Sécurisation des transmissions — déploiement cahier numérique',
                'source' => PacSource::Audit->value,
                'source_id' => $auditTermine->id,
                'constat' => 'Transmissions non tracées — écart majeur HAS.',
                'responsable' => 'Thomas Dupont',
                'echeance' => now()->subWeeks(2)->format('Y-m-d'),
            ])
            ->create();
        ActionAmelioration::factory()->forPlan($pacTermine)->realisee()->create([
            'description' => 'Déploiement de l\'application mobile sur tous les téléphones intervenants.',
        ]);
        ActionAmelioration::factory()->forPlan($pacTermine)->realisee()->create([
            'description' => 'Formation des intervenants à l\'usage du cahier numérique.',
        ]);

        $this->command->info('  ✓ 8 beneficiaries, 28+ interventions, 5 care plans, 8+ incidents, 3 audits, 3 PAC seeded');

        $this->seedSecondaryStructures();

        $this->printAccounts($accounts);
    }

    /**
     * Provision 3 additional structures so the platform admin dashboard
     * has cross-tenant data to display (varied types, tiers, statuses,
     * one suspended, one with critical incidents).
     *
     * Idempotent — keyed off the structure code.
     */
    private function seedSecondaryStructures(): void
    {
        $blueprints = [
            [
                'code' => 'SOLEIL',
                'name' => 'Soleil de Provence',
                'type' => StructureType::SAAD,
                'tier' => StructureTier::Pro,
                'status' => StructureStatus::Active,
                'dirigeant_email' => 'dirigeant.soleil@demo.fr',
                'dirigeant_first' => 'Hélène',
                'dirigeant_last' => 'Roux',
                'intervenants' => 6,
                'beneficiaries' => 12,
                'critical_incident' => true,
            ],
            [
                'code' => 'NORDSANTE',
                'name' => 'Nord Santé Soins',
                'type' => StructureType::SSIAD,
                'tier' => StructureTier::Premium,
                'status' => StructureStatus::Active,
                'dirigeant_email' => 'dirigeant.nord@demo.fr',
                'dirigeant_first' => 'Bertrand',
                'dirigeant_last' => 'Lefèvre',
                'intervenants' => 4,
                'beneficiaries' => 9,
                'critical_incident' => false,
            ],
            [
                'code' => 'MAINSDOR',
                'name' => "Mains d'Or — CCAS",
                'type' => StructureType::CCAS,
                'tier' => StructureTier::Essential,
                'status' => StructureStatus::Suspended,
                'dirigeant_email' => 'dirigeant.mainsdor@demo.fr',
                'dirigeant_first' => 'Patricia',
                'dirigeant_last' => 'Garnier',
                'intervenants' => 2,
                'beneficiaries' => 4,
                'critical_incident' => false,
            ],
        ];

        foreach ($blueprints as $blueprint) {
            $this->seedSecondaryStructure($blueprint);
        }

        $this->command->info('  ✓ 3 secondary structures seeded for the platform admin dashboard');
    }

    /**
     * @param  array{
     *     code: string,
     *     name: string,
     *     type: StructureType,
     *     tier: StructureTier,
     *     status: StructureStatus,
     *     dirigeant_email: string,
     *     dirigeant_first: string,
     *     dirigeant_last: string,
     *     intervenants: int,
     *     beneficiaries: int,
     *     critical_incident: bool
     * }  $blueprint
     */
    private function seedSecondaryStructure(array $blueprint): void
    {
        $structure = Structure::firstWhere('code', $blueprint['code'])
            ?? Structure::factory()->create([
                'code' => $blueprint['code'],
                'name' => $blueprint['name'],
                'type' => $blueprint['type']->value,
                'tier' => $blueprint['tier']->value,
                'status' => $blueprint['status']->value,
            ]);

        // Bind tenant context so BelongsToStructure auto-fills structure_id and
        // role assignments target the correct team scope.
        app()->instance('current_structure', $structure);
        app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());

        $dirigeant = User::firstWhere('email', $blueprint['dirigeant_email'])
            ?? User::factory()
                ->forStructure($structure)
                ->state([
                    'first_name' => $blueprint['dirigeant_first'],
                    'last_name' => $blueprint['dirigeant_last'],
                    'email' => $blueprint['dirigeant_email'],
                    'password' => Hash::make('password'),
                    'type' => UserType::Dirigeant->value,
                ])
                ->create();
        if (! $dirigeant->hasRole('dirigeant')) {
            $dirigeant->assignRole('dirigeant');
        }

        // Skip volume seeding if already done for this structure.
        if (Beneficiary::query()->where('structure_id', $structure->id)->count() > 0) {
            return;
        }

        $intervenants = User::factory()
            ->forStructure($structure)
            ->count($blueprint['intervenants'])
            ->state(['type' => UserType::Intervenant->value])
            ->create();
        $intervenants->each(fn (User $u) => $u->assignRole('intervenant'));

        $beneficiaries = Beneficiary::factory()
            ->forStructure($structure)
            ->count($blueprint['beneficiaries'])
            ->create();

        $beneficiaries->each(function (Beneficiary $beneficiary, int $i) use ($intervenants, $structure) {
            $intervenant = $intervenants[$i % max(1, $intervenants->count())] ?? $intervenants->first();

            if ($intervenant !== null) {
                IntervenantAssignment::factory()
                    ->between($intervenant, $beneficiary)
                    ->create();

                Intervention::factory()
                    ->count(2)
                    ->completed()
                    ->forBeneficiary($beneficiary)
                    ->forIntervenant($intervenant)
                    ->state(['structure_id' => $structure->id])
                    ->create();

                Intervention::factory()
                    ->planned()
                    ->forBeneficiary($beneficiary)
                    ->forIntervenant($intervenant)
                    ->state(['structure_id' => $structure->id])
                    ->create();
            }
        });

        if ($blueprint['critical_incident'] && $intervenants->isNotEmpty()) {
            Incident::factory()
                ->forStructure($structure)
                ->declaredBy($intervenants->first())
                ->grave()
                ->state([
                    'categorie' => CategorieIncident::Chute->value,
                    'description' => 'Chute avec hospitalisation. Dossier ouvert, ARS notifiée.',
                    'lieu' => 'Domicile bénéficiaire',
                    'avec_blessure_physique' => true,
                    'avec_hospitalisation' => true,
                ])
                ->create();
        }

        // Few minor incidents so the per-tenant table is not all zeroes.
        if ($intervenants->isNotEmpty()) {
            Incident::factory()
                ->count(2)
                ->forStructure($structure)
                ->declaredBy($intervenants->first())
                ->state(['categorie' => CategorieIncident::Autre->value])
                ->create();
        }
    }

    /** @param  array<int, array{email: string}>  $accounts */
    private function printAccounts(array $accounts): void
    {
        $this->command->info('');
        $this->command->info('  Demo accounts (password: "password"):');
        $this->command->info('  ── Plateforme ──');
        $this->command->info('    admin@platform.fr           (super_admin — /admin)');
        $this->command->info('  ── Structure DEMO ──');
        foreach ($accounts as $a) {
            $this->command->info("    {$a['email']}");
        }
        $this->command->info('    intervenant2@demo.fr');
        $this->command->info('    rh@demo.fr');
        $this->command->info('  ── Structures secondaires (dashboard plateforme) ──');
        $this->command->info('    dirigeant.soleil@demo.fr     (Soleil de Provence — SAAD Pro, actif)');
        $this->command->info('    dirigeant.nord@demo.fr       (Nord Santé Soins — SSIAD Premium, actif)');
        $this->command->info('    dirigeant.mainsdor@demo.fr   (Mains d\'Or — CCAS, suspendu)');
        $this->command->info('');
    }
}
