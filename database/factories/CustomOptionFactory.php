<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomOptionField;
use App\Models\CustomOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomOption>
 */
class CustomOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'field_key' => $this->faker->randomElement(CustomOptionField::cases())->value,
            'value' => $this->faker->unique()->slug(2),
            'label' => $this->faker->words(3, true),
            'sort_order' => $this->faker->numberBetween(0, 99),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forField(CustomOptionField $field): static
    {
        return $this->state(['field_key' => $field->value]);
    }
}
