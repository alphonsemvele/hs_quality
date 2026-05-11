<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Structure;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phase 2 / E4 — Quarterly access review.
 *
 * Yields one row per (structure, user) with the user's effective
 * permissions in that structure's tenant scope. Used by the
 * `access-review:export` Artisan command (CSV) and reachable from
 * any future compliance UI without re-implementing the iteration.
 *
 * Tenancy note: this is intentionally a cross-tenant operation —
 * compliance must see every structure's users. Spatie's team
 * context is set per structure inside the loop so role + permission
 * lookups resolve correctly under `team_foreign_key = structure_id`.
 *
 * Memory: uses `cursor()` to stream rows. Safe for large tenant counts
 * (the audit grows with users × structures).
 */
class AccessReviewExportService
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @return iterable<int, array<string, scalar|null>>
     */
    public function rows(?string $structureId = null): iterable
    {
        $structuresQuery = Structure::query()->orderBy('code');
        if ($structureId !== null) {
            $structuresQuery->where('id', $structureId);
        }

        foreach ($structuresQuery->cursor() as $structure) {
            $this->permissionRegistrar->setPermissionsTeamId($structure->getKey());

            $users = User::query()
                ->where('structure_id', $structure->getKey())
                ->orderBy('email')
                ->cursor();

            foreach ($users as $user) {
                yield $this->buildRow($user, $structure);
            }
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    private function buildRow(User $user, Structure $structure): array
    {
        $roles = $user->getRoleNames()->sort()->values()->all();
        $permissions = $user->getAllPermissions()->pluck('name')->sort()->values()->all();

        return [
            'structure_id' => $structure->getKey(),
            'structure_code' => $structure->code,
            'structure_nom' => $structure->name,
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'type' => $user->type?->value,
            'statut' => $user->status,
            'mfa_enrolled' => $user->two_factor_confirmed_at !== null ? 'oui' : 'non',
            'is_platform_admin' => $user->is_platform_admin ? 'oui' : 'non',
            'roles' => implode(';', $roles),
            'permissions_count' => count($permissions),
            'permissions' => implode(';', $permissions),
        ];
    }
}
