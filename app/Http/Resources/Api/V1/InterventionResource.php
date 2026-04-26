<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterventionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'structure_id' => $this->structure_id,
            'beneficiary_id' => $this->beneficiary_id,
            'intervenant_id' => $this->intervenant_id,
            'care_plan_id' => $this->care_plan_id,
            'planned_date' => $this->planned_date?->toDateString(),
            'planned_start_time' => $this->planned_start_time,
            'planned_end_time' => $this->planned_end_time,
            'actual_start_at' => $this->actual_start_at?->toISOString(),
            'actual_end_at' => $this->actual_end_at?->toISOString(),
            'status' => $this->status?->value,
            'visit_mode' => $this->visit_mode?->value,
            'cancellation_reason' => $this->cancellation_reason,
            'beneficiary' => $this->whenLoaded('beneficiary', fn () => new BeneficiaryResource($this->beneficiary)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
