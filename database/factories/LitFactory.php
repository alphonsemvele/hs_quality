<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class LitFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'service_id' => Service::factory(),
            'chambre' => fake()->regexify('[A-Za-z0-9]{20}'),
            'type' => fake()->randomElement(["standard","soins_intensifs","reanimation","pediatrique","maternite"]),
            'statut' => fake()->randomElement(["libre","occupe","maintenance","reserve"]),
            'equipements' => '{}',
            'tarif_journalier' => fake()->randomFloat(2, 0, 99999999.99),
        ];
    }
}
