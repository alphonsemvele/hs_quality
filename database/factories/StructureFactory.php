<?php

namespace Database\Factories;

use App\Enums\StatutStructure;
use App\Enums\TierStructure;
use App\Enums\TypeStructure;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Structure>
 */
class StructureFactory extends Factory
{
    protected $model = Structure::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'nom' => fake('fr_FR')->randomElement(['SAAD', 'SSIAD', 'SPASAD']) . ' ' . fake('fr_FR')->city(),
            'type' => fake()->randomElement(TypeStructure::cases())->value,
            'adresse' => fake('fr_FR')->address(),
            'siret' => fake()->numerify('##############'),
            'tier' => TierStructure::Essentiel->value,
            'statut' => StatutStructure::Active->value,
        ];
    }

    public function saad(): static
    {
        return $this->state(fn () => ['type' => TypeStructure::SAAD->value]);
    }

    public function ssiad(): static
    {
        return $this->state(fn () => ['type' => TypeStructure::SSIAD->value]);
    }

    public function pro(): static
    {
        return $this->state(fn () => ['tier' => TierStructure::Pro->value]);
    }

    public function premium(): static
    {
        return $this->state(fn () => ['tier' => TierStructure::Premium->value]);
    }

    public function suspendue(): static
    {
        return $this->state(fn () => ['statut' => StatutStructure::Suspendue->value]);
    }
}
