<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserType;
use App\Models\User;

/**
 * Frontend ability map shared with Inertia on every page.
 *
 * Keys are coarse, page-level abilities (not the fine-grained Spatie
 * permissions) so the React layer can hide whole pages and action
 * buttons whenever the current persona has no business clicking them.
 *
 * Backend authorization is still enforced by Policies + Spatie
 * permissions — this map ONLY drives UI visibility.
 *
 * Source: project RBAC matrix agreed with stakeholders. Keep aligned
 * with `database/seeders/RoleSeeder.php` when the matrix evolves.
 *
 * @phpstan-type Abilities array<string, bool>
 */
final class UserAbilities
{
    /**
     * Every ability key the frontend may check. Listing them once here
     * makes the contract explicit for the `Can` component and tests.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            'interventions.view',
            'interventions.create',
            'interventions.update',
            'interventions.delete',
            'incidents.view',
            'incidents.create',
            'incidents.analyze',
            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'audits.view',
            'audits.manage',
            'plans_amelioration.view',
            'plans_amelioration.manage',
            'indicateurs.view',
            'qvct.view',
            'qvct.manage',
            'communication.view',
            'communication.post',
            'formations.view',
            'formations.manage',
            'users.manage',
            'options.manage',
            'admin.structures',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return self::empty();
        }

        // Platform admins (super_admin) only see the cross-tenant surface.
        if ($user->is_platform_admin === true) {
            return array_merge(self::empty(), [
                'admin.structures' => true,
            ]);
        }

        $type = $user->type instanceof UserType
            ? $user->type
            : UserType::tryFrom((string) $user->type);

        $abilities = match ($type) {
            UserType::Dirigeant => self::dirigeant(),
            UserType::Coordinateur => self::coordinateur(),
            UserType::ReferentQualite => self::referentQualite(),
            UserType::Intervenant => self::intervenant(),
            UserType::Rh => self::rh(),
            default => [],
        };

        return array_merge(self::empty(), $abilities);
    }

    /**
     * @return array<string, bool>
     */
    private static function empty(): array
    {
        return array_fill_keys(self::keys(), false);
    }

    /**
     * @return array<string, bool>
     */
    private static function intervenant(): array
    {
        return [
            'interventions.view' => true,
            'interventions.update' => true, // own interventions only — backend filters
            'incidents.view' => true,
            'incidents.create' => true,
            'beneficiaries.view' => true,
            'communication.view' => true,
            'communication.post' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function coordinateur(): array
    {
        return [
            'interventions.view' => true,
            'interventions.create' => true,
            'interventions.update' => true,
            'interventions.delete' => true,
            'incidents.view' => true,
            'incidents.create' => true,
            'incidents.analyze' => true,
            'beneficiaries.view' => true,
            'beneficiaries.create' => true,
            'beneficiaries.update' => true,
            'qvct.view' => true,
            'communication.view' => true,
            'communication.post' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function dirigeant(): array
    {
        return [
            'interventions.view' => true,
            'interventions.create' => true,
            'interventions.update' => true,
            'interventions.delete' => true,
            'incidents.view' => true,
            'incidents.create' => true,
            'incidents.analyze' => true,
            'beneficiaries.view' => true,
            'beneficiaries.create' => true,
            'beneficiaries.update' => true,
            'audits.view' => true,
            'audits.manage' => true,
            'plans_amelioration.view' => true,
            'plans_amelioration.manage' => true,
            'indicateurs.view' => true,
            'qvct.view' => true,
            'qvct.manage' => true,
            'communication.view' => true,
            'communication.post' => true,
            'formations.view' => true,
            'formations.manage' => true,
            'users.manage' => true,
            'options.manage' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function referentQualite(): array
    {
        return [
            'interventions.view' => true,
            'incidents.view' => true,
            'incidents.analyze' => true,
            'beneficiaries.view' => true,
            'audits.view' => true,
            'audits.manage' => true,
            'plans_amelioration.view' => true,
            'plans_amelioration.manage' => true,
            'indicateurs.view' => true,
            'qvct.view' => true,
            'communication.view' => true,
            'communication.post' => true,
            'options.manage' => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function rh(): array
    {
        return [
            'qvct.view' => true,
            'qvct.manage' => true,
            'communication.view' => true,
            'communication.post' => true,
            'formations.view' => true,
            'formations.manage' => true,
            'users.manage' => true,
        ];
    }
}
