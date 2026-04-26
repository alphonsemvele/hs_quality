<?php

namespace App\Enums;

enum GraviteIncident: string
{
    case Mineur = 'mineur';
    case Significatif = 'significatif';
    case Grave = 'grave';
    case Critique = 'critique';

    /** CDC §6.3 — grave and critique must be reported to the ARS within 24h. */
    public function requiresARSNotification(): bool
    {
        return match ($this) {
            self::Grave, self::Critique => true,
            default => false,
        };
    }
}
