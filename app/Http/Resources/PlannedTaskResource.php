<?php

namespace App\Http\Resources;

use App\Models\PlannedTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PlannedTask
 */
class PlannedTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'care_plan_id' => $this->care_plan_id,

            'title' => $this->title,
            'description' => $this->description,

            'frequency' => $this->frequency?->value,
            'frequency_label' => $this->frequency?->label(),
            'frequency_details' => $this->frequency_details,

            'duration_minutes' => $this->duration_minutes,
            'task_order' => $this->task_order,
            'mandatory' => $this->mandatory,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
