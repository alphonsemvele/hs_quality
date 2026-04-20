<?php

namespace App\Http\Requests\Interventions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;
use Illuminate\Validation\Rule;

class UpdateInterventionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $intervention = $this->route('intervention');

        return $intervention instanceof Intervention
            && $this->user()->can('update', $intervention);
    }

    public function rules(): array
    {
        return [
            'planned_date' => ['sometimes', 'date'],
            'planned_start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'planned_end_time' => ['sometimes', 'nullable', 'date_format:H:i', 'after:planned_start_time'],
            'care_plan_id' => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('care_plans', 'id')
                    ->where('structure_id', $this->currentStructureId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'planned_end_time.after' => "L'heure de fin doit être postérieure à l'heure de début.",
        ];
    }
}
