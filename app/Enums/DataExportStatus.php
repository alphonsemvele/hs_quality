<?php

declare(strict_types=1);

namespace App\Enums;

enum DataExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Processing => 'En cours',
            self::Ready => 'Prêt à télécharger',
            self::Failed => 'Échec',
            self::Expired => 'Expiré',
        };
    }

    public function isDownloadable(): bool
    {
        return $this === self::Ready;
    }
}
