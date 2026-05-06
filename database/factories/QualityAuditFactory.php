<?php

namespace Database\Factories;

use App\Enums\AuditStatus;
use App\Enums\Referentiel;
use App\Models\QualityAudit;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityAudit>
 */
class QualityAuditFactory extends Factory
{
    protected $model = QualityAudit::class;

    public function definition(): array
    {
        return [
            'titre' => fake()->sentence(4),
            'referentiel' => fake()->randomElement(Referentiel::cases())->value,
            'description' => fake()->optional()->paragraph(),
            'date_audit' => fake()->dateTimeBetween('-3 months', '+1 month')->format('Y-m-d'),
            'auditeur' => fake()->name(),
            'statut' => AuditStatus::Planifie->value,
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

    public function enCours(): static
    {
        return $this->state(['statut' => AuditStatus::EnCours->value]);
    }

    public function termine(int $score = 85): static
    {
        return $this->state([
            'statut' => AuditStatus::Termine->value,
            'finalized_at' => now(),
            'score' => $score,
        ]);
    }

    public function annule(): static
    {
        return $this->state([
            'statut' => AuditStatus::Annule->value,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
