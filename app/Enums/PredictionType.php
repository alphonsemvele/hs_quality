<?php

declare(strict_types=1);

namespace App\Enums;

enum PredictionType: string
{
    case RisqueBurnout = 'risque_burnout';
    case PerteAutonomie = 'perte_autonomie';
    case AnalyseRapport = 'analyse_rapport';

    public function label(): string
    {
        return match ($this) {
            self::RisqueBurnout => 'Risque burnout',
            self::PerteAutonomie => 'Perte d\'autonomie',
            self::AnalyseRapport => 'Analyse sémantique rapport',
        };
    }

    /** Subject entity the prediction is about (informs input_snapshot shape). */
    public function subject(): string
    {
        return match ($this) {
            self::RisqueBurnout => 'intervenant',
            self::PerteAutonomie => 'beneficiaire',
            self::AnalyseRapport => 'intervention',
        };
    }
}
