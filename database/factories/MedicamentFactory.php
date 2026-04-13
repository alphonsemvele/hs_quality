<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicamentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{200}'),
            'dci' => fake()->regexify('[A-Za-z0-9]{200}'),
            'forme' => fake()->randomElement(["comprime","gelule","sirop","injectable","pommade","collyre","suppositoire","solution","poudre","autre"]),
            'dosage' => fake()->regexify('[A-Za-z0-9]{50}'),
            'categorie' => fake()->randomElement(["antalgique","antibiotique","anti_inflammatoire","antidiabetique","antihypertenseur","antipaludeen","antiseptique","vitamine","autre"]),
            'voie_administration' => fake()->randomElement(["orale","injectable","cutanee","rectale","oculaire","nasale","auriculaire","autre"]),
            'conditionnement' => fake()->regexify('[A-Za-z0-9]{100}'),
            'stock_actuel' => fake()->randomNumber(),
            'stock_minimum' => fake()->randomNumber(),
            'stock_maximum' => fake()->randomNumber(),
            'prix_achat' => fake()->randomFloat(2, 0, 99999999.99),
            'prix_vente' => fake()->randomFloat(2, 0, 99999999.99),
            'tva' => fake()->randomFloat(2, 0, 999.99),
            'fournisseur_id' => Fournisseur::factory(),
            'date_expiration' => fake()->date(),
            'emplacement' => fake()->regexify('[A-Za-z0-9]{50}'),
            'ordonnance_obligatoire' => fake()->boolean(),
            'actif' => fake()->boolean(),
            'notes' => fake()->text(),
        ];
    }
}
