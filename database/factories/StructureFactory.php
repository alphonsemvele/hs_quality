<?php

namespace Database\Factories;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
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
        $type = fake()->randomElement(['SAAD', 'SSIAD', 'SPASAD']);

        return [
            'code' => strtoupper(Str::random(8)),
            'name' => $type.' '.fake('fr_FR')->city(),
            'type' => fake()->randomElement(StructureType::cases())->value,
            'address' => fake('fr_FR')->address(),
            'siret' => fake()->numerify('##############'),
            'tier' => StructureTier::Essential->value,
            'status' => StructureStatus::Active->value,
        ];
    }

    public function saad(): static
    {
        return $this->state(fn () => ['type' => StructureType::SAAD->value]);
    }

    public function ssiad(): static
    {
        return $this->state(fn () => ['type' => StructureType::SSIAD->value]);
    }

    public function pro(): static
    {
        return $this->state(fn () => ['tier' => StructureTier::Pro->value]);
    }

    public function premium(): static
    {
        return $this->state(fn () => ['tier' => StructureTier::Premium->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => StructureStatus::Suspended->value]);
    }
}
