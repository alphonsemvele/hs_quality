<?php

namespace App\Enums;

/**
 * The 6 personas. Values kept in French as they are the canonical role
 * names used across Spatie Permission, French sector documentation, and
 * stakeholder conversations. Translating "coordinateur" → "coordinator"
 * loses the specific French regulatory / cultural meaning.
 *
 * See: references/rbac/matrix.md
 */
enum UserType: string
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
        return array_filter(self::cases(), fn (self $t) => $t->requiresMfa());
    }
}
