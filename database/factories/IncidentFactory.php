<?php

namespace Database\Factories;

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'declared_by' => User::factory(),
            'occurred_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'categorie' => fake()->randomElement(CategorieIncident::cases())->value,
            'gravite' => GraviteIncident::Mineur->value,
            'statut' => StatutIncident::Declare->value,
            'description' => fake()->sentence(),
            'lieu' => fake()->optional()->address(),
            'avec_deces' => false,
            'avec_hospitalisation' => false,
            'avec_blessure_physique' => false,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function declaredBy(User $user): static
    {
        return $this->state([
            'declared_by' => $user->id,
            'structure_id' => $user->structure_id,
        ]);
    }

    public function grave(): static
    {
        return $this->state([
            'gravite' => GraviteIncident::Grave->value,
            'avec_hospitalisation' => true,
        ]);
    }

    public function critique(): static
    {
        return $this->state([
            'gravite' => GraviteIncident::Critique->value,
            'categorie' => CategorieIncident::SituationDanger->value,
        ]);
    }

    public function enAnalyse(): static
    {
        return $this->state(['statut' => StatutIncident::EnAnalyse->value]);
    }

    public function planActions(): static
    {
        return $this->state(['statut' => StatutIncident::PlanActions->value]);
    }

    public function clos(): static
    {
        return $this->state([
            'statut' => StatutIncident::Clos->value,
            'closed_at' => now(),
        ]);
    }
}
