<?php

namespace Database\Factories;

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditGrid>
 */
class AuditGridFactory extends Factory
{
    protected $model = AuditGrid::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => fake()->randomElement([
                'Grille HAS — Évaluation interne',
                'Grille ISO 9001 — Service à la personne',
                'Grille AFNOR NF X50-056',
                'Grille personnalisée Q3 2026',
            ]),
            'description' => fake('fr_FR')->paragraph(),
            'source' => fake()->randomElement(array_column(AuditGridSource::cases(), 'value')),
            'weight_scheme' => ['type' => 'equal'],
            'is_active' => true,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function fromHas(): self
    {
        return $this->state(['source' => AuditGridSource::Has->value]);
    }
}
