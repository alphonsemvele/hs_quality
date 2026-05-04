<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentSuivi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentSuivi>
 */
class IncidentSuiviFactory extends Factory
{
    protected $model = IncidentSuivi::class;

    public function definition(): array
    {
        $notes = [
            'Famille contactée, ils confirment les faits signalés.',
            'Visite de contrôle effectuée, situation stabilisée.',
            'Réunion d\'équipe tenue, protocole révisé transmis.',
            'Médecin traitant informé par courrier sécurisé.',
            'Actions correctives en cours de déploiement.',
            'Point de suivi avec le coordinateur, avancement conforme.',
            'Retour bénéficiaire positif, amélioration constatée.',
        ];

        return [
            'incident_id' => Incident::factory(),
            'author_id' => null,
            'note' => fake()->randomElement($notes),
        ];
    }

    public function forIncident(Incident $incident): static
    {
        return $this->state(fn () => [
            'structure_id' => $incident->structure_id,
            'incident_id' => $incident->id,
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn () => ['author_id' => $user->id]);
    }
}
