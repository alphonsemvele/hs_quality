<?php

namespace Database\Factories;

use App\Enums\InterventionStatus;
use App\Enums\VisitMode;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'intervenant_id' => User::factory(),
            'planned_date' => fake()->dateTimeBetween('-7 days', '+7 days')->format('Y-m-d'),
            'planned_start_time' => fake()->time('H:i:s'),
            'planned_end_time' => null,
            'status' => InterventionStatus::Planned->value,
            'visit_mode' => VisitMode::Web->value,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function forIntervenant(User $user): static
    {
        return $this->state(['intervenant_id' => $user->id]);
    }

    public function forBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state([
            'beneficiary_id' => $beneficiary->id,
            'structure_id' => $beneficiary->structure_id,
        ]);
    }

    public function forCarePlan(CarePlan $plan): static
    {
        return $this->state([
            'care_plan_id' => $plan->id,
            'structure_id' => $plan->structure_id,
        ]);
    }

    public function planned(): static
    {
        return $this->state(['status' => InterventionStatus::Planned->value]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => InterventionStatus::InProgress->value,
            'actual_start_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => InterventionStatus::Completed->value,
            'actual_start_at' => now()->subHours(2),
            'actual_end_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => InterventionStatus::Cancelled->value,
            'cancellation_reason' => 'Annulé par le bénéficiaire',
        ]);
    }
}
