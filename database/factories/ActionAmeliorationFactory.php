<?php

namespace Database\Factories;

use App\Enums\ActionStatus;
use App\Models\ActionAmelioration;
use App\Models\PlanAmelioration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActionAmelioration>
 */
class ActionAmeliorationFactory extends Factory
{
    protected $model = ActionAmelioration::class;

    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'responsable' => fake()->optional()->name(),
            'echeance' => fake()->optional()->dateTimeBetween('+1 week', '+2 months')?->format('Y-m-d'),
            'statut' => ActionStatus::Planifiee->value,
        ];
    }

    public function forPlan(PlanAmelioration $plan): static
    {
        return $this->state([
            'plan_amelioration_id' => $plan->id,
            'structure_id' => $plan->structure_id,
        ]);
    }

    public function realisee(): static
    {
        return $this->state([
            'statut' => ActionStatus::Realisee->value,
            'realise_at' => now(),
        ]);
    }

    public function enCours(): static
    {
        return $this->state(['statut' => ActionStatus::EnCours->value]);
    }
}
