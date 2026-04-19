<?php

namespace App\Http\Resources;

use App\Models\IntervenantAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IntervenantAssignment
 *
 * Shape an assignment for inline display on the beneficiary show page.
 * The intervenant relation is expected to be eager-loaded by the caller;
 * we surface only the fields that don't reveal anything beyond what the
 * coordinateur already sees in the team directory.
 */
class IntervenantAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'intervenant' => $this->whenLoaded('intervenant', fn () => [
                'id' => $this->intervenant->id,
                'full_name' => $this->intervenant->fullName(),
                'email' => $this->intervenant->email,
            ]),
            'assigned_by' => $this->whenLoaded('assignedBy', fn () => $this->assignedBy ? [
                'id' => $this->assignedBy->id,
                'full_name' => $this->assignedBy->fullName(),
            ] : null),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'unassigned_at' => $this->unassigned_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'notes' => $this->notes,
        ];
    }
}
