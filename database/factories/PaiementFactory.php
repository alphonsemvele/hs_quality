<?php

namespace Database\Factories;

use App\Models\Facture;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaiementFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'facture_id' => Facture::factory(),
            'date_paiement' => fake()->dateTime(),
            'montant' => fake()->randomFloat(2, 0, 9999999999.99),
            'mode_paiement' => fake()->randomElement(["especes","carte_bancaire","mobile_money","cheque","virement","assurance"]),
            'reference' => fake()->regexify('[A-Za-z0-9]{100}'),
            'banque' => fake()->regexify('[A-Za-z0-9]{100}'),
            'telephone_mobile_money' => fake()->regexify('[A-Za-z0-9]{20}'),
            'recu_par' => User::factory()->create()->recu_par,
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["en_attente","confirme","annule"]),
            'recu_par_id' => User::factory(),
        ];
    }
}
