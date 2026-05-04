<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentActionCorrective;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentActionCorrective>
 */
class IncidentActionCorrectiveFactory extends Factory
{
    protected $model = IncidentActionCorrective::class;

    public function definition(): array
    {
        $descriptions = [
            'Former les intervenants au protocole de prévention des chutes',
            'Installer des barres d\'appui dans la salle de bain du bénéficiaire',
            'Réviser le protocole d\'administration médicamenteuse',
            'Organiser une réunion d\'équipe pour partager le retour d\'expérience',
            'Mettre à jour la fiche d\'évaluation des risques du domicile',
            'Planifier une visite de contrôle renforcée sur 2 semaines',
            'Mettre en place un cahier de transmission numérique',
            'Réaliser un audit surprise des pratiques terrain',
        ];

        return [
            'incident_id' => Incident::factory(),
            'description' => fake()->randomElement($descriptions),
            'responsable_id' => null,
            'echeance' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'statut' => fake()->randomElement(['en_cours', 'planifiee', 'done']),
            'realise_at' => null,
        ];
    }

    public function forIncident(Incident $incident): static
    {
        return $this->state(fn () => [
            'structure_id' => $incident->structure_id,
            'incident_id' => $incident->id,
        ]);
    }

    public function withResponsable(User $user): static
    {
        return $this->state(fn () => ['responsable_id' => $user->id]);
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'statut' => 'done',
            'realise_at' => now(),
        ]);
    }
}
