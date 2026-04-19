<?php

namespace Database\Factories;

use App\Enums\CarePlanStatus;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarePlan>
 */
class CarePlanFactory extends Factory
{
    protected $model = CarePlan::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'structure_id' => Structure::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'created_by_user_id' => null,
            'title' => 'Plan d\'accompagnement '.$start->format('Y'),
            'objectives' => fake('fr_FR')->paragraph(3),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => null,
            'status' => CarePlanStatus::Draft->value,
            'archived_at' => null,
            'archived_reason' => null,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(fn () => ['structure_id' => $structure->id]);
    }

    public function forBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state(fn () => [
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => CarePlanStatus::Active->value]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => CarePlanStatus::Archived->value,
            'archived_at' => now(),
            'archived_reason' => 'Remplacé par une nouvelle version',
        ]);
    }
}
