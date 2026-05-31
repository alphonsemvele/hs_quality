<?php

namespace App\Enums;

enum CategorieIncident: string
{
    case Chute = 'chute';
    case Agression = 'agression';
    case ErreurMedicamenteuse = 'erreur_medicamenteuse';
    case MaltraitanceSuspecte = 'maltraitance_suspecte';
    case SituationDanger = 'situation_danger';
    case Autre = 'autre';

    /**
     * Human-readable French label. Single source of truth for category
     * display so raw enum values (e.g. "erreur_medicamenteuse") never leak
     * into the UI (incident list/detail, indicator breakdowns).
     */
    public function label(): string
    {
        return match ($this) {
            self::Chute => 'Chute',
            self::Agression => 'Agression',
            self::ErreurMedicamenteuse => 'Erreur médicamenteuse',
            self::MaltraitanceSuspecte => 'Maltraitance suspectée',
            self::SituationDanger => 'Situation de danger',
            self::Autre => 'Autre',
        };
    }
}
