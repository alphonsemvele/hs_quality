<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QvctFrequency;
use App\Models\QvctQuestionnaire;
use App\Models\Structure;
use Illuminate\Database\Seeder;

/**
 * Seeds a default QVCT baromètre questionnaire per structure that
 * doesn't already have one. Idempotent — re-runnable safely.
 *
 * Default template uses the four CDC-aligned categories so the
 * WeakSignalDetector emits useful signals out of the box without an
 * RH having to author questions on day one.
 *
 * Spec: PHASE2_PROGRESS.md M3.5 — seeders.
 */
class QvctSeeder extends Seeder
{
    public function run(): void
    {
        Structure::query()->each(function (Structure $structure): void {
            // Bind tenant context so BelongsToStructure auto-fills + scope is happy.
            app()->instance('current_structure', $structure);

            $exists = QvctQuestionnaire::query()
                ->where('structure_id', $structure->id)
                ->where('title', self::DEFAULT_TITLE)
                ->exists();

            if ($exists) {
                return;
            }

            QvctQuestionnaire::create([
                'structure_id' => $structure->id,
                'title' => self::DEFAULT_TITLE,
                'version' => 1,
                'frequency' => QvctFrequency::Monthly->value,
                'questions' => self::DEFAULT_QUESTIONS,
                'is_active' => true,
            ]);
        });
    }

    public const DEFAULT_TITLE = 'Baromètre QVCT mensuel';

    /** @var array<int, array{key: string, label: string, scale: string, category: string}> */
    public const DEFAULT_QUESTIONS = [
        [
            'key' => 'morale',
            'label' => 'Comment évaluez-vous votre moral ce mois-ci ?',
            'scale' => '1-5',
            'category' => 'baisse_morale',
        ],
        [
            'key' => 'charge',
            'label' => 'Votre charge de travail vous semble-t-elle soutenable ?',
            'scale' => '1-5',
            'category' => 'surcharge',
        ],
        [
            'key' => 'relations',
            'label' => 'Vos relations avec votre équipe et votre coordinateur sont-elles bonnes ?',
            'scale' => '1-5',
            'category' => 'conflit_relationnel',
        ],
        [
            'key' => 'isolement',
            'label' => 'Vous sentez-vous suffisamment soutenu(e) lors de vos interventions à domicile ?',
            'scale' => '1-5',
            'category' => 'isolement_professionnel',
        ],
    ];
}
