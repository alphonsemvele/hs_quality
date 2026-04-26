<?php

namespace App\Enums;

enum StatutIncident: string
{
    case Declare = 'declare';
    case EnAnalyse = 'en_analyse';
    case PlanActions = 'plan_actions';
    case Clos = 'clos';

    public function isClosed(): bool
    {
        return $this === self::Clos;
    }
}
