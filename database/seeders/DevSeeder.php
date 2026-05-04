<?php

namespace Database\Seeders;

use App\Enums\CarePlanStatus;
use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Incident;
use App\Models\IncidentActionCorrective;
use App\Models\IncidentSuivi;
use App\Models\IntervenantAssignment;
use App\Models\Intervention;
use App\Models\PlannedTask;
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

        // Skip if already seeded (idempotent).
        if (Beneficiary::where('structure_id', $structure->id)->count() >= 5) {
            $this->command->info('  Demo data already exists — skipping.');
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

        $this->command->info('  ✓ 8 beneficiaries, 28+ interventions, 5 care plans, 8+ incidents seeded');
        $this->printAccounts($accounts);
    }

    /** @param  array<int, array{email: string}>  $accounts */
    private function printAccounts(array $accounts): void
    {
        $this->command->info('');
        $this->command->info('  Demo accounts (password: "password"):');
        foreach ($accounts as $a) {
            $this->command->info("    {$a['email']}");
        }
        $this->command->info('    intervenant2@demo.fr');
        $this->command->info('    rh@demo.fr');
        $this->command->info('');
    }
}
