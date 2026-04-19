<?php

namespace App\Enums;

enum TaskFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case OnDemand = 'on_demand';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Quotidien',
            self::Weekly => 'Hebdomadaire',
            self::Monthly => 'Mensuel',
            self::OnDemand => 'À la demande',
            self::Custom => 'Personnalisé',
        };
    }
}
