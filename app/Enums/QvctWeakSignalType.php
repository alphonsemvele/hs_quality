<?php

namespace App\Enums;

/**
 * Categories of weak signal the WeakSignalDetector emits per campaign × team.
 * Spec: CDC §M3 line "Weak-signal detection: morale drop, overload,
 * relational conflicts".
 *
 * `BaisseMorale`        — average baromètre score dropped > threshold vs prior
 * `Surcharge`           — overload signal (workload questions skew negative)
 * `ConflitRelationnel`  — relational-conflict questions skew negative
 * `IsolementProfessional` — isolation indicator (CDC §3.1 risk #1 for SAAD)
 *
 * Tagged by the detector so the référent RH dashboard can filter and route.
 */
enum QvctWeakSignalType: string
{
    case BaisseMorale = 'baisse_morale';
    case Surcharge = 'surcharge';
    case ConflitRelationnel = 'conflit_relationnel';
    case IsolementProfessional = 'isolement_professionnel';

    public function label(): string
    {
        return match ($this) {
            self::BaisseMorale => 'Baisse de morale',
            self::Surcharge => 'Surcharge de travail',
            self::ConflitRelationnel => 'Conflit relationnel',
            self::IsolementProfessional => 'Isolement professionnel',
        };
    }
}
