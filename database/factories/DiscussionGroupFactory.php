<?php

namespace Database\Factories;

use App\Models\DiscussionGroup;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscussionGroup>
 */
class DiscussionGroupFactory extends Factory
{
    protected $model = DiscussionGroup::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => fake()->randomElement([
                'Équipe Paris Centre',
                'Secteur Lyon',
                'Bonnes pratiques du soir',
                'Coordination QVCT',
            ]),
            'description' => fake('fr_FR')->sentence(),
            'kind' => fake()->randomElement(['team', 'secteur', 'thematic']),
            'created_by' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }
}
