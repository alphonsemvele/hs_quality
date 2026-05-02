<?php

namespace Database\Factories;

use App\Enums\PacActionStatus;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PacAction>
 */
class PacActionFactory extends Factory
{
    protected $model = PacAction::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'pac_id' => Pac::factory(),
            'source_audit_response_id' => null,
            'title' => 'Action: '.fake('fr_FR')->sentence(4),
            'description' => fake('fr_FR')->paragraph(),
            'responsible_user_id' => null,
            'due_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'status' => PacActionStatus::Pending->value,
            'evidence_url' => null,
        ];
    }

    public function forPac(Pac $pac): self
    {
        return $this->state([
            'structure_id' => $pac->structure_id,
            'pac_id' => $pac->id,
        ]);
    }
}
