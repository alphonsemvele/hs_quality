<?php

namespace Database\Factories;

use App\Enums\QvctWeakSignalType;
use App\Models\QvctCampaign;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctWeakSignal>
 */
class QvctWeakSignalFactory extends Factory
{
    protected $model = QvctWeakSignal::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'campaign_id' => QvctCampaign::factory(),
            'team_tag' => null,
            'signal_type' => QvctWeakSignalType::BaisseMorale->value,
            'details' => [
                'mean_score' => 2.4,
                'threshold' => 3.0,
                'sample_size' => 12,
                'summary' => 'Baisse de morale détectée — moyenne 2.4 sur seuil 3.0.',
            ],
            'severity' => 2,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
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
