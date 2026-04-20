<?php

namespace App\Enums;

enum VisitMode: string
{
    case Mobile = 'mobile';
    case Web = 'web';
    case Batch = 'batch';

    public function label(): string
    {
        return match ($this) {
            self::Mobile => 'Mobile',
            self::Web => 'Web',
            self::Batch => 'Import automatique',
        };
    }
}
