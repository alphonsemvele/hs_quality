<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

// is_platform_admin is a boolean User column — no Spatie role needed.

/**
 * Demo structures for development and STATUS.md walkthroughs.
 *
 * Creates 3 tenants of different types and tiers, plus a single platform
 * super_admin (operator account) at the global scope. Every other seeded
 * persona belongs to one of these tenants.
 *
 * Run idempotently — safe to re-execute. Uses code uniqueness as the key.
 *
 * Run: php artisan db:seed --class=StructureSeeder
 *      (depends on RoleSeeder, which it calls automatically if needed)
 */
class StructureSeeder extends Seeder
{
    /** @var list<array{code: string, name: string, type: StructureType, tier: StructureTier}> */
    private const STRUCTURES = [
        ['code' => 'DEMO-SAAD', 'name' => 'SAAD Horizon Douala', 'type' => StructureType::SAAD, 'tier' => StructureTier::Pro],
        ['code' => 'DEMO-SSIAD', 'name' => 'SSIAD Centre Yaoundé', 'type' => StructureType::SSIAD, 'tier' => StructureTier::Premium],
        ['code' => 'DEMO-SPASAD', 'name' => 'SPASAD Nord', 'type' => StructureType::SPASAD, 'tier' => StructureTier::Essential],
    ];

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $this->seedSuperAdmin();

        foreach (self::STRUCTURES as $spec) {
            $this->seedStructure($spec);
        }
    }

    /**
     * Operator account for the platform admin surface (/admin/structures).
     * Lives outside any tenant — `is_platform_admin = true`, no Spatie role.
     */
    private function seedSuperAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'platform-admin@demo.fr'],
            [
                'first_name' => 'Platform',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'employee_number' => 'PLATADMIN',
                'type' => UserType::Dirigeant->value,
                'status' => 'active',
                'is_platform_admin' => true,
                'email_verified_at' => now(),
                // structure_id intentionally null — this user is platform-scope.
            ],
        );
    }

    /**
     * @param  array{code: string, name: string, type: StructureType, tier: StructureTier}  $spec
     */
    private function seedStructure(array $spec): void
    {
        $structure = Structure::firstWhere('code', $spec['code']);

        if ($structure === null) {
            $structure = Structure::create([
                'code' => $spec['code'],
                'name' => $spec['name'],
                'type' => $spec['type']->value,
                'tier' => $spec['tier']->value,
                'status' => 'active',
            ]);
        }

        $email = sprintf('dirigeant-%s@demo.fr', mb_strtolower(str_replace('DEMO-', '', $spec['code'])));
        $dirigeant = User::firstWhere('email', $email);

        if ($dirigeant === null) {
            $dirigeant = User::create([
                'structure_id' => $structure->id,
                'first_name' => 'Dirigeant',
                'last_name' => $spec['type']->value,
                'email' => $email,
                'password' => Hash::make('password'),
                'employee_number' => mb_strtoupper(substr($spec['code'], 0, 8)),
                'type' => UserType::Dirigeant->value,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
        if (! $dirigeant->hasRole('dirigeant')) {
            $dirigeant->assignRole('dirigeant');
        }
    }
}
