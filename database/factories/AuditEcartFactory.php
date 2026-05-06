<?php

namespace Database\Factories;

use App\Enums\EcartGravite;
use App\Models\AuditEcart;
use App\Models\QualityAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEcart>
 */
class AuditEcartFactory extends Factory
{
    protected $model = AuditEcart::class;

    public function definition(): array
    {
        return [
            'critere' => fake()->sentence(3),
            'constat' => fake()->paragraph(),
            'gravite' => EcartGravite::Mineur->value,
            'action_corrective' => fake()->optional()->sentence(),
        ];
    }

    public function forAudit(QualityAudit $audit): static
    {
        return $this->state([
            'quality_audit_id' => $audit->id,
            'structure_id' => $audit->structure_id,
        ]);
    }

    public function majeur(): static
    {
        return $this->state(['gravite' => EcartGravite::Majeur->value]);
    }

    public function critique(): static
    {
        return $this->state(['gravite' => EcartGravite::Critique->value]);
    }
}
