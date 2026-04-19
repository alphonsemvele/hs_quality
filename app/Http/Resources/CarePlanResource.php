<?php

namespace App\Http\Resources;

use App\Models\CarePlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CarePlan
 *
 * Summary shape for a CarePlan. The `objectives` field is encrypted at the
 * column level AND may contain health context — include it here because
 * this resource is returned only to users who already have care_plans.view
 * permission (filtered by tenant via global scope), but any endpoint
 * returning objectives should be log_sensitive_read-guarded just like the
 * Beneficiary dossier.
 */
class CarePlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'beneficiary_id' => $this->beneficiary_id,
            'title' => $this->title,
            'objectives' => $this->objectives,

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'is_active' => $this->isActive(),
            'is_archived' => $this->isArchived(),

            'archived_at' => $this->archived_at?->toIso8601String(),
            'archived_reason' => $this->archived_reason,

            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->fullName(),
            ]),

            'tasks' => PlannedTaskResource::collection($this->whenLoaded('tasks')),
            'tasks_count' => $this->whenCounted('tasks'),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
