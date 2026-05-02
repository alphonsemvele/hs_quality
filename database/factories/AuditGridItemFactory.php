<?php

namespace Database\Factories;

use App\Enums\AuditItemScale;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditGridItem>
 */
class AuditGridItemFactory extends Factory
{
    protected $model = AuditGridItem::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'audit_grid_id' => AuditGrid::factory(),
            'title' => fake('fr_FR')->sentence(6),
            'description' => fake('fr_FR')->paragraph(),
            'scale' => AuditItemScale::Binary->value,
            'max_points' => 1,
            'evidence_required' => false,
            'position' => fake()->numberBetween(0, 99),
        ];
    }

    public function forGrid(AuditGrid $grid): self
    {
        return $this->state([
            'structure_id' => $grid->structure_id,
            'audit_grid_id' => $grid->id,
        ]);
    }
}
