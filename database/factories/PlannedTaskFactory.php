<?php

namespace Database\Factories;

use App\Enums\TaskFrequency;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannedTask>
 */
class PlannedTaskFactory extends Factory
{
    protected $model = PlannedTask::class;

    public function definition(): array
    {
        $titles = [
            'Toilette du matin',
            'Préparation du petit-déjeuner',
            'Prise des médicaments',
            'Aide à la mobilité',
            'Préparation du déjeuner',
            'Contrôle tension artérielle',
            'Aide au coucher',
            'Surveillance glycémie',
            'Entretien du logement',
            'Accompagnement courses',
        ];

        return [
            'care_plan_id' => CarePlan::factory(),
            // structure_id is auto-populated from parent via PlannedTask::booted()
            'title' => fake()->randomElement($titles),
            'description' => null,
            'frequency' => TaskFrequency::Daily->value,
            'frequency_details' => null,
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'task_order' => 0,
            'mandatory' => true,
        ];
    }

    public function forCarePlan(CarePlan $plan): static
    {
        return $this->state(fn () => [
            'care_plan_id' => $plan->id,
            'structure_id' => $plan->structure_id,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'frequency' => TaskFrequency::Weekly->value,
            'frequency_details' => ['days' => ['monday', 'wednesday', 'friday']],
        ]);
    }

    public function optional(): static
    {
        return $this->state(fn () => ['mandatory' => false]);
    }

    public function order(int $position): static
    {
        return $this->state(fn () => ['task_order' => $position]);
    }
}
