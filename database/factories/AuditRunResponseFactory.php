<?php

namespace Database\Factories;

use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditRunResponse>
 */
class AuditRunResponseFactory extends Factory
{
    protected $model = AuditRunResponse::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'audit_run_id' => AuditRun::factory(),
            'audit_grid_item_id' => AuditGridItem::factory(),
            'score' => fake()->randomFloat(2, 0, 5),
            'comment' => fake('fr_FR')->sentence(),
            'evidence_url' => null,
            'recorded_by' => null,
            'recorded_at' => now(),
        ];
    }

    public function forRunAndItem(AuditRun $run, AuditGridItem $item): self
    {
        return $this->state([
            'structure_id' => $run->structure_id,
            'audit_run_id' => $run->id,
            'audit_grid_item_id' => $item->id,
        ]);
    }
}
