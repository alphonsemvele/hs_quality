<?php

namespace Database\Factories;

use App\Enums\QvctFrequency;
use App\Models\QvctQuestionnaire;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctQuestionnaire>
 */
class QvctQuestionnaireFactory extends Factory
{
    protected $model = QvctQuestionnaire::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => 'Baromètre QVCT '.fake()->word(),
            'version' => 1,
            'frequency' => QvctFrequency::Monthly->value,
            'questions' => [
                ['key' => 'morale', 'label' => 'Comment évaluez-vous votre moral cette semaine ?', 'scale' => '1-5', 'category' => 'baisse_morale'],
                ['key' => 'charge', 'label' => 'Votre charge de travail vous semble-t-elle soutenable ?', 'scale' => '1-5', 'category' => 'surcharge'],
                ['key' => 'relations', 'label' => 'Vos relations avec votre équipe sont-elles bonnes ?', 'scale' => '1-5', 'category' => 'conflit_relationnel'],
                ['key' => 'isolement', 'label' => 'Vous sentez-vous soutenu(e) lors des interventions ?', 'scale' => '1-5', 'category' => 'isolement_professionnel'],
            ],
            'is_active' => true,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }
}
