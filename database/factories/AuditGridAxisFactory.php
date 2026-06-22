<?php

namespace Database\Factories;

use App\Models\AuditGrid;
use App\Models\AuditGridAxis;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditGridAxis>
 */
class AuditGridAxisFactory extends Factory
{
    protected $model = AuditGridAxis::class;

    public function definition(): array
    {
        // Unique sequence guarantees no collision on the (audit_grid_id, code)
        // unique index when a test creates multiple axes for the same grid.
        $n = static::sequenceCounter();

        return [
            'structure_id' => Structure::factory(),
            'audit_grid_id' => AuditGrid::factory(),
            'code' => 'axe_'.$n,
            'title' => fake('fr_FR')->sentence(3),
            'description' => null,
            'position' => $n,
        ];
    }

    private static int $counter = 0;

    private static function sequenceCounter(): int
    {
        return ++self::$counter;
    }

    public function forGrid(AuditGrid $grid): self
    {
        return $this->state([
            'structure_id' => $grid->structure_id,
            'audit_grid_id' => $grid->id,
        ]);
    }
}
