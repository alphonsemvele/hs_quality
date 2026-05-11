<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2/3 — Indicateurs & KPIs computed from M3 (QVCT), M5 (Formations),
 * and M6 (Audits) once those domains exist. Demo dataset shipped for the
 * `local` environment so the dashboard renders without seeded data.
 */
class IndicateurController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/indicateurs/index', [
            'indicateurs' => $demo ? $this->demoIndicateurs() : $this->emptyIndicateurs(),
            'series' => $demo ? $this->demoSeries() : [],
            'breakdowns' => $demo ? $this->demoBreakdowns() : $this->emptyBreakdowns(),
        ]);
    }

    /**
     * @return array<string, int|float|null>
     */
    private function demoIndicateurs(): array
    {
        return [
            'conformite' => 78,
            'pac_completion' => 58,
            'qvct_moyen' => 6.8,
            'formations_a_jour' => 83,
            'incidents_ce_mois' => 3,
            'interventions_ce_mois' => 47,
        ];
    }

    /**
     * @return array<string, null>
     */
    private function emptyIndicateurs(): array
    {
        return [
            'conformite' => null,
            'pac_completion' => null,
            'qvct_moyen' => null,
            'formations_a_jour' => null,
            'incidents_ce_mois' => null,
            'interventions_ce_mois' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoSeries(): array
    {
        $months = ['Nov', 'Déc', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai'];

        return [
            [
                'key' => 'conformite',
                'label' => 'Conformité (%)',
                'tone' => 'sage',
                'data' => $this->build($months, [62, 65, 68, 71, 73, 76, 78]),
            ],
            [
                'key' => 'qvct',
                'label' => 'Score QVCT moyen',
                'tone' => 'brand',
                'data' => $this->build($months, [6.1, 6.3, 6.5, 6.4, 6.6, 6.7, 6.8]),
            ],
            [
                'key' => 'incidents',
                'label' => 'Incidents déclarés',
                'tone' => 'danger',
                'data' => $this->build($months, [8, 6, 5, 7, 4, 4, 3]),
            ],
            [
                'key' => 'interventions',
                'label' => 'Interventions réalisées',
                'tone' => 'neutral',
                'data' => $this->build($months, [38, 41, 43, 39, 45, 46, 47]),
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function demoBreakdowns(): array
    {
        return [
            'incidents_categorie' => [
                ['label' => 'Chute', 'value' => 12, 'tone' => 'danger'],
                ['label' => 'Médicament', 'value' => 5, 'tone' => 'warning'],
                ['label' => 'Maltraitance', 'value' => 2, 'tone' => 'brand'],
                ['label' => 'Autre', 'value' => 8, 'tone' => 'neutral'],
            ],
            'audits_referentiel' => [
                ['label' => 'HAS', 'value' => 14, 'tone' => 'sage'],
                ['label' => 'ISO 9001', 'value' => 6, 'tone' => 'brand'],
                ['label' => 'AFNOR', 'value' => 3, 'tone' => 'warning'],
            ],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function emptyBreakdowns(): array
    {
        return [
            'incidents_categorie' => [],
            'audits_referentiel' => [],
        ];
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int|float>  $values
     * @return array<int, array{label: string, value: int|float}>
     */
    private function build(array $labels, array $values): array
    {
        return array_map(
            fn ($label, $value) => ['label' => $label, 'value' => $value],
            $labels,
            $values,
        );
    }
}
