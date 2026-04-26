<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Bulk dataset for k6 load tests. Run AFTER DevSeeder so the demo
 * intervenant / coordinateur accounts exist.
 *
 *   php artisan db:seed --class=DevSeeder
 *   php artisan db:seed --class=LoadTestSeeder
 *
 * Creates:
 *   - 200 beneficiaries in the demo structure
 *   - 200 planned interventions for the demo intervenant scheduled today
 *
 * This matches the plan's "200 intervenants × 5 interventions/day" load
 * model — the load test runs 200 VUs each owning 1 intervenant identity,
 * but for simplicity we put 200 interventions on the single seeded
 * intervenant. The auth path is what we're stress-testing, not RBAC
 * fan-out.
 *
 * NEVER run this seeder on production or against real customer data.
 * The bulk records aren't tagged for cleanup.
 */
class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        $structure = Structure::firstWhere('code', 'DEMO');
        $intervenant = User::firstWhere('email', 'intervenant@demo.fr');

        if ($structure === null || $intervenant === null) {
            $this->command->error('LoadTestSeeder requires DevSeeder. Run `php artisan db:seed --class=DevSeeder` first.');

            return;
        }

        app()->instance('current_structure', $structure);

        $beneficiaries = Beneficiary::factory()
            ->forStructure($structure)
            ->count(200)
            ->create();

        foreach ($beneficiaries as $beneficiary) {
            IntervenantAssignment::factory()
                ->forStructure($structure)
                ->between($intervenant, $beneficiary)
                ->create();

            Intervention::factory()
                ->forStructure($structure)
                ->state([
                    'beneficiary_id' => $beneficiary->id,
                    'intervenant_id' => $intervenant->id,
                    'planned_date' => now()->format('Y-m-d'),
                    'planned_start_time' => sprintf('%02d:00:00', random_int(7, 18)),
                    'status' => 'planned',
                ])
                ->create();
        }

        $this->command->info(sprintf(
            'LoadTestSeeder: created %d beneficiaries + %d planned interventions for %s.',
            $beneficiaries->count(),
            $beneficiaries->count(),
            $intervenant->email,
        ));
    }
}
