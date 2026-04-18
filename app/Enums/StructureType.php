<?php

namespace App\Enums;

/**
 * Structure types — French home-care regulatory categories.
 * Values kept in French (SAAD / SSIAD / SPASAD / ESAD) as they are the
 * official regulatory identifiers used across the sector.
 */
enum StructureType: string
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
