<?php

namespace Database\Factories;

use App\Enums\QvctActionPlanItemStatus;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctActionPlanItem>
 */
class QvctActionPlanItemFactory extends Factory
{
    protected $model = QvctActionPlanItem::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'action_plan_id' => QvctActionPlan::factory(),
            'title' => 'Action: '.fake('fr_FR')->sentence(4),
            'description' => fake('fr_FR')->paragraph(),
            'responsible_user_id' => null,
            'due_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'status' => QvctActionPlanItemStatus::Pending->value,
            'impact_measurement_target' => 'Réduire le score de surcharge sous 2.5',
            'impact_measurement_actual' => null,
            'impact_measured_at' => null,
        ];
    }

    public function forActionPlan(QvctActionPlan $plan): self
    {
        return $this->state([
            'structure_id' => $plan->structure_id,
            'action_plan_id' => $plan->id,
        ]);
    }
}
