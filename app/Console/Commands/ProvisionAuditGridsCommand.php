<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuditGridSource;
use App\Models\Structure;
use App\Services\AuditGridLibrary;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Phase 2 / M6.9 — provision reference audit grids for one or all structures.
 *
 * Usage:
 *   php artisan audit-grids:provision
 *   php artisan audit-grids:provision --structure=<uuid>
 *   php artisan audit-grids:provision --source=has
 */
class ProvisionAuditGridsCommand extends Command
{
    protected $signature = 'audit-grids:provision
        {--structure= : UUID of a specific structure (default: all structures)}
        {--source= : AuditGridSource value to provision (default: all available)}';

    protected $description = 'Idempotently provision HAS / ISO 9001 / AFNOR reference grids for structures';

    public function handle(AuditGridLibrary $library): int
    {
        $structures = $this->resolveStructures();

        if ($structures->isEmpty()) {
            $this->warn('Aucune structure trouvée.');

            return self::SUCCESS;
        }

        $source = $this->resolveSource($library);

        $this->info(sprintf(
            'Provisionnement de %d structure(s) — source : %s',
            $structures->count(),
            $source !== null ? $source->value : 'toutes disponibles',
        ));

        foreach ($structures as $structure) {
            app()->instance('current_structure', $structure);

            if ($source !== null) {
                $library->provisionForStructure($structure, $source);
                $this->line("  ✓ {$structure->name} [{$source->value}]");
            } else {
                $library->provisionAllForStructure($structure);
                $this->line("  ✓ {$structure->name} [toutes sources]");
            }
        }

        $this->info('Terminé.');

        return self::SUCCESS;
    }

    private function resolveStructures(): Collection
    {
        $uuid = $this->option('structure');

        if ($uuid !== null) {
            return Structure::query()->where('id', $uuid)->get();
        }

        return Structure::query()->get();
    }

    private function resolveSource(AuditGridLibrary $library): ?AuditGridSource
    {
        $value = $this->option('source');

        if ($value === null) {
            return null;
        }

        $source = AuditGridSource::tryFrom((string) $value);

        if ($source === null || ! $library->fixtureExists($source)) {
            $this->error("Source inconnue ou fixture absente : {$value}");

            return null;
        }

        return $source;
    }
}
