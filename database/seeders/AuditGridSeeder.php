<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\Structure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the HAS reference grid (M6.7) per structure. Each tenant gets
 * its own copy so customization stays isolated. Idempotent — safe to
 * re-run.
 *
 * The fixture lives at database/seeders/fixtures/has-grid.json so the
 * grid can be updated without a code change. Future ISO 9001 + AFNOR
 * fixtures will follow the same pattern.
 */
class AuditGridSeeder extends Seeder
{
    public function run(): void
    {
        $payload = json_decode(
            (string) file_get_contents(__DIR__.'/fixtures/has-grid.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        Structure::query()->each(function (Structure $structure) use ($payload): void {
            app()->instance('current_structure', $structure);

            $exists = AuditGrid::query()
                ->where('structure_id', $structure->id)
                ->where('source', AuditGridSource::Has->value)
                ->where('title', $payload['title'])
                ->exists();

            if ($exists) {
                return;
            }

            DB::transaction(function () use ($structure, $payload): void {
                $grid = AuditGrid::create([
                    'structure_id' => $structure->id,
                    'title' => $payload['title'],
                    'description' => $payload['description'] ?? null,
                    'source' => AuditGridSource::Has->value,
                    'weight_scheme' => $payload['weight_scheme'] ?? ['type' => 'equal'],
                    'is_active' => true,
                ]);

                foreach ($payload['items'] as $item) {
                    AuditGridItem::create([
                        'structure_id' => $structure->id,
                        'audit_grid_id' => $grid->id,
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'scale' => $item['scale'],
                        'max_points' => $item['max_points'] ?? 1,
                        'evidence_required' => $item['evidence_required'] ?? false,
                        'position' => $item['position'] ?? 0,
                    ]);
                }
            });
        });
    }
}
