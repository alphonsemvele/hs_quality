<?php

namespace Database\Factories;

use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Models\PlanAmelioration;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanAmelioration>
 */
class PlanAmeliorationFactory extends Factory
{
    protected $model = PlanAmelioration::class;

    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(4),
            'source' => fake()->randomElement(PacSource::cases())->value,
            'source_id' => null,
            'constat' => fake()->paragraph(),
            'responsable' => fake()->name(),
            'echeance' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'statut' => PlanAmeliorationStatus::Ouvert->value,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state([
            'created_by' => $user->id,
            'structure_id' => $user->structure_id,
        ]);
    }

    public function fromAudit(string $auditId): static
    {
        return $this->state([
            'source' => PacSource::Audit->value,
            'source_id' => $auditId,
        ]);
    }

    public function enCours(): static
    {
        return $this->state(['statut' => PlanAmeliorationStatus::EnCours->value]);
    }

    public function termine(): static
    {
        return $this->state([
            'statut' => PlanAmeliorationStatus::Termine->value,
            'closed_at' => now(),
        ]);
    }

    public function annule(): static
    {
        return $this->state([
            'statut' => PlanAmeliorationStatus::Annule->value,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
