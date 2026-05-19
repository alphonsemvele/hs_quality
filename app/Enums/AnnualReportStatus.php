<?php

declare(strict_types=1);

namespace App\Enums;

enum AnnualReportStatus: string
{
    case EnAttente = 'en_attente';
    case EnCours = 'en_cours';
    case Genere = 'genere';
    case Echoue = 'echoue';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de génération',
            self::EnCours => 'Génération en cours',
            self::Genere => 'Rapport disponible',
            self::Echoue => 'Échec de génération',
        };
    }
}
