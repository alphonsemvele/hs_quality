<?php

namespace App\Enums;

enum EcartGravite: string
{
    case Mineur = 'mineur';
    case Majeur = 'majeur';
    case Critique = 'critique';

    public function label(): string
    {
        return match ($this) {
            self::Mineur => 'Mineur',
            self::Majeur => 'Majeur',
            self::Critique => 'Critique',
        };
    }

    /**
     * Major and critical findings should automatically open a corrective
     * action plan (PAC) entry. Minor findings are noted but don't escalate.
     */
    public function requiresPac(): bool
    {
        return $this === self::Majeur || $this === self::Critique;
    }

    /**
     * Weight used in the score calculation. A 100% conformity score means
     * zero weighted écarts; majeur and critique penalize harder.
     */
    public function scoreWeight(): int
    {
        return match ($this) {
            self::Mineur => 1,
            self::Majeur => 3,
            self::Critique => 5,
        };
    }
}
