<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Discharged = 'discharged';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
            self::Discharged => 'Sorti du service',
            self::Deceased => 'Décédé',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Discharged, self::Deceased], true);
    }
}
