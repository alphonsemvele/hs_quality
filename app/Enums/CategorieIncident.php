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
}
