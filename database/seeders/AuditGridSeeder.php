<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Structure;
use App\Services\AuditGridLibrary;
use Illuminate\Database\Seeder;

/**
 * Seeds all available reference grids (HAS, ISO 9001, AFNOR NF X50-056)
 * per structure, delegating idempotency and transaction logic to
 * AuditGridLibrary. Safe to re-run; existing grids are not duplicated.
 */
class AuditGridSeeder extends Seeder
{
    public function __construct(private readonly AuditGridLibrary $library) {}

    public function run(): void
    {
        Structure::query()->each(function (Structure $structure): void {
            app()->instance('current_structure', $structure);
            $this->library->provisionAllForStructure($structure);
        });
    }
}
