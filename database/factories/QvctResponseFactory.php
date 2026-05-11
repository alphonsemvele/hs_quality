<?php

namespace Database\Factories;

use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctResponse>
 */
class QvctResponseFactory extends Factory
{
    protected $model = QvctResponse::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'campaign_id' => QvctCampaign::factory(),
            'answers' => [
                'morale' => fake()->numberBetween(1, 5),
                'charge' => fake()->numberBetween(1, 5),
                'relations' => fake()->numberBetween(1, 5),
                'isolement' => fake()->numberBetween(1, 5),
            ],
            'team_tag' => fake()->randomElement([null, 'team_paris', 'team_lyon']),
            'submitted_at' => now(),
        ];
    }

    public function forCampaign(QvctCampaign $campaign): self
    {
        return $this->state([
            'structure_id' => $campaign->structure_id,
            'campaign_id' => $campaign->id,
        ]);
    }
}
