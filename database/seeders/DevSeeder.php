<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dev seeder — creates a known structure and one user per role with predictable
 * credentials so the API can be exercised manually from Postman / curl.
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

        $accounts = [
            ['type' => UserType::Dirigeant, 'role' => 'dirigeant', 'email' => 'dirigeant@demo.fr'],
            ['type' => UserType::Coordinateur, 'role' => 'coordinateur', 'email' => 'coordinateur@demo.fr'],
            ['type' => UserType::ReferentQualite, 'role' => 'referent_qualite', 'email' => 'qualite@demo.fr'],
            ['type' => UserType::Intervenant, 'role' => 'intervenant', 'email' => 'intervenant@demo.fr'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstWhere('email', $account['email'])
                ?? User::factory()
                    ->forStructure($structure)
                    ->state([
                        'first_name' => ucfirst($account['role']),
                        'last_name' => 'Demo',
                        'email' => $account['email'],
                        'password' => Hash::make('password'),
                        'type' => $account['type']->value,
                    ])
                    ->create();

            if (! $user->hasRole($account['role'])) {
                $user->assignRole($account['role']);
            }
        }

        // Sample data so list endpoints aren't empty (only seed once).
        if (Beneficiary::where('structure_id', $structure->id)->doesntExist()) {
            $beneficiaries = Beneficiary::factory()
                ->forStructure($structure)
                ->count(5)
                ->create();

            $intervenant = User::where('email', 'intervenant@demo.fr')->first();

            foreach ($beneficiaries as $beneficiary) {
                Intervention::factory()
                    ->count(3)
                    ->forBeneficiary($beneficiary)
                    ->forIntervenant($intervenant)
                    ->create();
            }
        }

        $this->command->info('');
        $this->command->info('  Demo accounts created (password: "password"):');
        foreach ($accounts as $a) {
            $this->command->info("    {$a['email']}");
        }
        $this->command->info('');
    }
}
