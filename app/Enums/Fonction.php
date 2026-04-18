<?php

namespace App\Enums;

/**
 * The 6 personas on this project. Mirrors Spatie Permission role names.
 * See: references/rbac/matrix.md for the per-role permission matrix.
 */
enum Fonction: string
{
    case Intervenant = 'intervenant';
    case Coordinateur = 'coordinateur';
    case Dirigeant = 'dirigeant';
    case ReferentQualite = 'referent_qualite';
    case Rh = 'rh';
    case BeneficiairePortal = 'beneficiaire_portal';

    public function label(): string
    {
        return match ($this) {
            self::Intervenant => 'Intervenant à domicile',
            self::Coordinateur => 'Coordinateur / Responsable de secteur',
            self::Dirigeant => 'Dirigeant de structure',
            self::ReferentQualite => 'Référent qualité',
            self::Rh => 'Responsable RH / formation',
            self::BeneficiairePortal => 'Bénéficiaire / famille (portail)',
        };
    }

    public function requiresMfa(): bool
    {
        return in_array($this, [
            self::Dirigeant,
            self::ReferentQualite,
            self::Coordinateur,
            self::Rh,
        ], true);
    }

    /** @return array<int, self> */
    public static function mandatoryMfaRoles(): array
    {
        return array_filter(self::cases(), fn (self $f) => $f->requiresMfa());
    }
}
