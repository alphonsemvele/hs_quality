<?php

namespace Database\Factories;

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctCampaign>
 */
class QvctCampaignFactory extends Factory
{
    protected $model = QvctCampaign::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'questionnaire_id' => QvctQuestionnaire::factory(),
            'title' => 'Campagne baromètre — '.now()->format('M Y'),
            'opens_at' => now()->subDays(3)->toDateString(),
            'closes_at' => now()->addDays(11)->toDateString(),
            'status' => QvctCampaignStatus::Active->value,
            'target_team' => null,
            'launched_by' => null,
            'closed_at' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(fn () => [
            'structure_id' => $structure->id,
            'questionnaire_id' => QvctQuestionnaire::factory()->forStructure($structure),
        ]);
    }

    public function draft(): self
    {
        return $this->state(['status' => QvctCampaignStatus::Draft->value]);
    }

    public function closed(): self
    {
        return $this->state([
            'status' => QvctCampaignStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }
}
