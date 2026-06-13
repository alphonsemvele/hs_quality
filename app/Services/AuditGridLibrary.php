<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\AuditGridAxis;
use App\Models\AuditGridItem;
use App\Models\Structure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2 / M6.9 — reference grid template library.
 *
 * Owns the mapping between AuditGridSource values and the JSON fixture
 * files in database/seeders/fixtures/. A source is "available" when its
 * fixture file exists on disk; adding a new fixture file (M6.10 — ISO
 * 9001 / AFNOR NF X50-056) automatically makes that source available
 * to provisionAllForStructure() without any code change.
 *
 * Idempotency: provisionForStructure() checks for an existing grid with
 * the same (structure_id, source, title) triple before creating. Safe
 * to call repeatedly; safe to run during on-boarding, in seeders, and
 * in the audit-grids:provision Artisan command.
 */
class AuditGridLibrary
{
    private string $fixtureDir;

    public function __construct(string $fixtureDir = '')
    {
        $this->fixtureDir = $fixtureDir !== '' ? $fixtureDir : database_path('seeders/fixtures');
    }

    /**
     * Return the AuditGridSource cases that have a fixture file on disk
     * (Custom is excluded — custom grids are always authored from scratch).
     *
     * @return AuditGridSource[]
     */
    public function availableSources(): array
    {
        return array_values(array_filter(
            AuditGridSource::cases(),
            fn (AuditGridSource $source) => $source !== AuditGridSource::Custom
                && $this->fixtureExists($source),
        ));
    }

    public function fixtureExists(AuditGridSource $source): bool
    {
        $filename = $source->fixtureFilename();

        return $filename !== null && file_exists($this->fixtureDir.'/'.$filename);
    }

    /**
     * Load and decode the JSON fixture for a given source.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException when the fixture file does not exist.
     */
    public function loadFixture(AuditGridSource $source): array
    {
        if (! $this->fixtureExists($source)) {
            throw new \RuntimeException(
                "Fixture introuvable pour la source : {$source->value}",
            );
        }

        $filename = $source->fixtureFilename();

        return json_decode(
            (string) file_get_contents($this->fixtureDir.'/'.$filename),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * Idempotently provision one reference grid for a structure.
     *
     * Returns the existing AuditGrid if it was already provisioned, or
     * the newly created one. All items are created inside a transaction
     * so a mid-seeding failure leaves no orphaned grid.
     */
    public function provisionForStructure(Structure $structure, AuditGridSource $source): AuditGrid
    {
        $payload = $this->loadFixture($source);

        $existing = AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('source', $source->value)
            ->where('title', $payload['title'])
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($structure, $source, $payload): AuditGrid {
            $grid = AuditGrid::create([
                'structure_id' => $structure->id,
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'source' => $source->value,
                'weight_scheme' => $payload['weight_scheme'] ?? ['type' => 'equal'],
                'is_active' => true,
            ]);

            // Axes thématiques (grilles structurées comme SAP unifiée).
            $axisIdByCode = [];
            foreach ($payload['axes'] ?? [] as $axisPayload) {
                $axis = AuditGridAxis::create([
                    'structure_id' => $structure->id,
                    'audit_grid_id' => $grid->id,
                    'code' => $axisPayload['code'],
                    'title' => $axisPayload['title'],
                    'description' => $axisPayload['description'] ?? null,
                    'position' => $axisPayload['position'] ?? 0,
                ]);
                $axisIdByCode[$axisPayload['code']] = $axis->id;
            }

            foreach ($payload['items'] as $position => $item) {
                AuditGridItem::create([
                    'structure_id' => $structure->id,
                    'audit_grid_id' => $grid->id,
                    'axis_id' => isset($item['axis_code']) ? ($axisIdByCode[$item['axis_code']] ?? null) : null,
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'sources' => $item['sources'] ?? null,
                    'level' => $item['level'] ?? null,
                    'scale' => $item['scale'],
                    'max_points' => $item['max_points'] ?? 1,
                    'evidence_required' => $item['evidence_required'] ?? false,
                    'position' => $item['position'] ?? $position,
                ]);
            }

            return $grid;
        });
    }

    /**
     * Soft-delete every existing grid for (structure, source) then
     * re-provision from the current fixture. Used when a reference
     * fixture is updated (e.g. HAS unified SAP rollout) and operators
     * want each tenant onto the new content without touching historical
     * AuditRuns (which keep their FK to the soft-deleted grid items).
     */
    public function replaceForStructure(Structure $structure, AuditGridSource $source): AuditGrid
    {
        return DB::transaction(function () use ($structure, $source): AuditGrid {
            AuditGrid::withoutGlobalScopes()
                ->where('structure_id', $structure->id)
                ->where('source', $source->value)
                ->whereNull('deleted_at')
                ->get()
                ->each(fn (AuditGrid $g) => $g->delete());

            return $this->provisionForStructure($structure, $source);
        });
    }

    /**
     * Provision every available reference grid for a structure.
     * Sources whose fixture file does not exist are silently skipped.
     */
    public function provisionAllForStructure(Structure $structure): void
    {
        foreach ($this->availableSources() as $source) {
            try {
                $this->provisionForStructure($structure, $source);
            } catch (\Throwable $e) {
                Log::error('AuditGridLibrary: échec de provisionnement', [
                    'structure_id' => $structure->id,
                    'source' => $source->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
