<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Habilitation;
use App\Models\User;
use Carbon\CarbonInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * HabilitationService — record, renew, and expire diploma-style
 * qualifications. Spec: PHASE2_PROGRESS.md M5.8.
 *
 * Habilitations are mostly lifetime, so `expire()` is a manual op for
 * the rare case where a diploma is revoked or temporarily suspended.
 * `renew()` updates valid_from / valid_until in place — keep one row
 * per (user, type), accumulate audit trail through laravel-auditing.
 */
class HabilitationService
{
    /**
     * Record a new habilitation for a user. Pre-condition: caller has
     * already gone through the policy + form-request stack.
     */
    public function record(
        User $user,
        string $type,
        ?string $referenceNumber = null,
        ?CarbonInterface $validFrom = null,
        ?CarbonInterface $validUntil = null,
        ?string $evidencePath = null,
        ?User $recorder = null,
    ): Habilitation {
        return Habilitation::create([
            'structure_id' => $user->structure_id,
            'user_id' => $user->id,
            'type' => $type,
            'reference_number' => $referenceNumber,
            'valid_from' => $validFrom?->toDateString(),
            'valid_until' => $validUntil?->toDateString(),
            'evidence_path' => $evidencePath,
            'recorded_by' => $recorder?->id,
        ]);
    }

    /**
     * Renew an existing habilitation. Must already be valid (not in
     * a soft-deleted/expired state) — otherwise the caller should
     * record a new one rather than reviving.
     */
    public function renew(
        Habilitation $habilitation,
        CarbonInterface $newValidUntil,
        ?CarbonInterface $newValidFrom = null,
        ?string $newEvidencePath = null,
    ): Habilitation {
        if ($habilitation->trashed()) {
            throw new HttpException(409, 'Impossible de renouveler une habilitation supprimée.');
        }

        $habilitation->update([
            'valid_from' => $newValidFrom?->toDateString() ?? $habilitation->valid_from,
            'valid_until' => $newValidUntil->toDateString(),
            'evidence_path' => $newEvidencePath ?? $habilitation->evidence_path,
        ]);

        return $habilitation->fresh();
    }

    /**
     * Expire (soft-delete) a habilitation. Audit trail via Auditable
     * captures who and why. The model is recoverable via
     * `withTrashed()->restore()` if revoked in error.
     */
    public function expire(Habilitation $habilitation): void
    {
        $habilitation->delete();
    }
}
