<?php

namespace App\Enums;

/**
 * The origin of an improvement plan. The CDC requires that every PAC be
 * traceable to its trigger (audit finding, incident root-cause, QVCT
 * alert, beneficiary complaint, or directorial decision).
 */
enum PacSource: string
{
    case Audit = 'audit';
    case Incident = 'incident';
    case Qvct = 'qvct';
    case Reclamation = 'reclamation';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Audit => 'Audit',
            self::Incident => 'Incident',
            self::Qvct => 'Alerte QVCT',
            self::Reclamation => 'Réclamation',
            self::Autre => 'Autre',
        };
    }
}
