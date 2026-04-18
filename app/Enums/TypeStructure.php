<?php

namespace App\Enums;

enum TypeStructure: string
{
    case SAAD = 'saad';
    case SSIAD = 'ssiad';
    case SPASAD = 'spasad';
    case ESAD = 'esad';
    case Mandataire = 'mandataire';
    case CCAS = 'ccas';

    public function label(): string
    {
        return match ($this) {
            self::SAAD => 'Service d\'Aide et d\'Accompagnement à Domicile',
            self::SSIAD => 'Service de Soins Infirmiers à Domicile',
            self::SPASAD => 'Service Polyvalent d\'Aide et de Soins à Domicile',
            self::ESAD => 'Équipe Spécialisée Alzheimer à Domicile',
            self::Mandataire => 'Mandataire / Prestataire indépendant',
            self::CCAS => 'CCAS / Collectivité',
        };
    }
}
