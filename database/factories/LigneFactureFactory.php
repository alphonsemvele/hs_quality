<?php

namespace Database\Factories;

use App\Models\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

class LigneFactureFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'facture_id' => Facture::factory(),
            'designation' => fake()->regexify('[A-Za-z0-9]{255}'),
            'type' => fake()->randomElement(["consultation","acte_medical","medicament","analyse","imagerie","sejour","autre"]),
            'reference_id' => fake()->randomNumber(),
            'quantite' => fake()->randomNumber(),
            'prix_unitaire' => fake()->randomFloat(2, 0, 99999999.99),
            'remise' => fake()->randomFloat(2, 0, 99999999.99),
            'tva' => fake()->randomFloat(2, 0, 99999999.99),
            'prix_total' => fake()->randomFloat(2, 0, 99999999.99),
            'couvert_assurance' => fake()->boolean(),
        ];
    }
}
