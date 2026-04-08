<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TypeAnalyseFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{150}'),
            'categorie' => fake()->randomElement(["biochimie","hematologie","microbiologie","parasitologie","immunologie","hormonologie","serologie","autre"]),
            'description' => fake()->text(),
            'delai_resultat_heures' => fake()->randomNumber(),
            'prix' => fake()->randomFloat(2, 0, 99999999.99),
            'valeurs_normales' => '{}',
            'echantillon_requis' => fake()->randomElement(["sang","urine","selles","crachat","lcr","autre"]),
            'preparation_patient' => fake()->text(),
            'actif' => fake()->boolean(),
        ];
    }
}
