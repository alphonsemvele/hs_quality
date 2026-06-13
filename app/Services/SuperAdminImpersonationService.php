<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Structure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for super-admin "view as dirigeant" impersonation.
 *
 * State lives in the web session (never in DB) so the moment the super-admin
 * logs out or the session expires, all impersonation context evaporates. The
 * impersonation IS NOT a role grant — the underlying user remains the platform
 * admin; this service only signals to the rest of the system (TenantResolver,
 * BasePolicy, the User permission overrides, the Inertia banner) that the
 * current request should behave as if the actor were a dirigeant of the given
 * structure.
 *
 * Why session-only:
 *   - No DB writes on every navigation — zero overhead.
 *   - No risk of a stale row outliving a logout / browser close.
 *   - Per-tab is not what we want (sessions are per-browser); see {@see start()}.
 *
 * Why a hard timeout (4h):
 *   - A super-admin who forgets to "quitter" stays in dirigeant mode forever
 *     otherwise — actions get attributed to the impersonation context even when
 *     the human intent has long since shifted. Forced re-entry every 4h forces
 *     a deliberate decision.
 */
final class SuperAdminImpersonationService
{
    /**
     * Session keys are namespaced under "impersonation." so the whole bag can
     * be flushed in a single Session::forget('impersonation') if needed.
     */
    private const KEY_STRUCTURE_ID = 'impersonation.structure_id';

    private const KEY_STARTED_AT = 'impersonation.started_at';

    private const MAX_DURATION_MINUTES = 240;

    /**
     * Per-request memoisation of the resolved Structure model. Avoids hitting
     * the DB on every call (shared props serialisation, TenantResolver, policy
     * checks, all run in the same request).
     */
    private ?Structure $cachedStructure = null;

    private ?string $cachedStructureId = null;

    /**
     * Memoised list of permission names attached to the canonical `dirigeant`
     * Spatie role. Resolved lazily on the first impersonation permission check
     * of a request and reused for every subsequent check inside that request.
     *
     * @var list<string>|null
     */
    private ?array $cachedDirigeantPermissions = null;

    public function __construct(private readonly Session $session) {}

    /**
     * Enter the structure context. Only super-admins should call this; the
     * controller enforces the role check via the `super_admin` middleware
     * before invoking the service, so we trust the caller here.
     */
    public function start(Structure $structure): void
    {
        $this->session->put(self::KEY_STRUCTURE_ID, (string) $structure->getKey());
        $this->session->put(self::KEY_STARTED_AT, CarbonImmutable::now()->toIso8601String());

        $this->cachedStructure = $structure;
        $this->cachedStructureId = (string) $structure->getKey();
    }

    /**
     * Exit the structure context. Idempotent — safe to call when not active.
     */
    public function stop(): void
    {
        $this->session->forget([self::KEY_STRUCTURE_ID, self::KEY_STARTED_AT]);

        $this->cachedStructure = null;
        $this->cachedStructureId = null;
    }

    /**
     * Is the current request running inside an impersonation context that is
     * still within the hard duration limit? Expired sessions auto-clear, so
     * callers never see stale state — this keeps the rest of the codebase
     * (TenantResolver, BasePolicy, ...) free of timing logic.
     */
    public function isActive(): bool
    {
        if (! $this->session->has(self::KEY_STRUCTURE_ID)) {
            return false;
        }

        $startedAt = $this->startedAt();

        if ($startedAt === null) {
            $this->stop();

            return false;
        }

        if ($startedAt->diffInMinutes(CarbonImmutable::now()) >= self::MAX_DURATION_MINUTES) {
            $this->stop();

            return false;
        }

        return true;
    }

    /**
     * @return string|null structure UUID or null when impersonation is not active.
     */
    public function structureId(): ?string
    {
        if (! $this->isActive()) {
            return null;
        }

        return $this->cachedStructureId ??= (string) $this->session->get(self::KEY_STRUCTURE_ID);
    }

    public function structure(): ?Structure
    {
        $structureId = $this->structureId();

        if ($structureId === null) {
            return null;
        }

        if ($this->cachedStructure !== null && (string) $this->cachedStructure->getKey() === $structureId) {
            return $this->cachedStructure;
        }

        $structure = Structure::query()->find($structureId);

        if ($structure === null) {
            // Structure was deleted while a super-admin was inside it; bail out
            // cleanly rather than letting downstream code work with a ghost.
            $this->stop();

            return null;
        }

        return $this->cachedStructure = $structure;
    }

    public function startedAt(): ?CarbonImmutable
    {
        $iso = $this->session->get(self::KEY_STARTED_AT);

        if (! is_string($iso) || $iso === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($iso);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Convenience for the auditing resolver — returns the id of the real
     * super-admin whose session is currently impersonating, or null. Used to
     * populate the `impersonator_id` column on every audit row written while
     * impersonation is active.
     */
    public function impersonatorId(?User $user): ?int
    {
        if (! $this->isActive() || $user === null) {
            return null;
        }

        if ($user->is_platform_admin !== true) {
            return null;
        }

        return (int) $user->getKey();
    }

    /**
     * The permission names attached to the canonical `dirigeant` Spatie role.
     *
     * Used by {@see User::hasPermissionTo()} to decide whether a
     * super-admin's "view as dirigeant" session grants the specific
     * permission a policy is asking for. The list is memoised on the service
     * (request-scoped) so successive checks in the same request only hit the
     * DB once.
     *
     * @return list<string>
     */
    public function dirigeantPermissionNames(): array
    {
        if ($this->cachedDirigeantPermissions !== null) {
            return $this->cachedDirigeantPermissions;
        }

        $role = Role::query()
            ->whereNull(config('permission.column_names.team_foreign_key', 'team_id'))
            ->where('name', 'dirigeant')
            ->with('permissions:id,name')
            ->first();

        return $this->cachedDirigeantPermissions = $role
            ? $role->permissions->pluck('name')->all()
            : [];
    }
}
