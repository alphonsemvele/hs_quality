<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lifecycle operations for IntervenantAssignment. Enforces the
 * cross-structure invariant + active-assignment uniqueness.
 *
 * Error surface:
 *   422 — cross-structure assignment attempt
 *   409 — attempting to re-assign when an active assignment already exists
 */
class IntervenantAssignmentService
{
    public function assign(
        User $intervenant,
        Beneficiary $beneficiary,
        ?User $assignedBy = null,
        ?string $notes = null,
    ): IntervenantAssignment {
        if ($intervenant->structure_id !== $beneficiary->structure_id) {
            throw new HttpException(422, 'Cannot assign across structures.');
        }

        return DB::transaction(function () use ($intervenant, $beneficiary, $assignedBy, $notes): IntervenantAssignment {
            $activeExisting = IntervenantAssignment::query()
                ->active()
                ->where('user_id', $intervenant->id)
                ->where('beneficiary_id', $beneficiary->id)
                ->exists();

            if ($activeExisting) {
                throw new HttpException(409, 'This intervenant is already actively assigned to this beneficiary.');
            }

            return IntervenantAssignment::create([
                'structure_id' => $intervenant->structure_id,
                'user_id' => $intervenant->id,
                'beneficiary_id' => $beneficiary->id,
                'assigned_by_user_id' => $assignedBy?->id,
                'assigned_at' => now(),
                'notes' => $notes,
            ])->fresh();
        });
    }

    public function unassign(IntervenantAssignment $assignment, ?string $reason = null): IntervenantAssignment
    {
        if (! $assignment->isActive()) {
            throw new HttpException(409, 'Assignment is already unassigned.');
        }

        return DB::transaction(function () use ($assignment, $reason): IntervenantAssignment {
            $assignment->update([
                'unassigned_at' => now(),
                'notes' => $reason
                    ? ($assignment->notes ? $assignment->notes."\n\n[Fin d'assignation] ".$reason : '[Fin d\'assignation] '.$reason)
                    : $assignment->notes,
            ]);

            return $assignment->fresh();
        });
    }
}
