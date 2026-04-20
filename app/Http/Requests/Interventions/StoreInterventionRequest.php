<?php

namespace App\Http\Requests\Interventions;

use App\Enums\UserType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;
use Illuminate\Validation\Rule;

class StoreInterventionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Intervention::class);
    }

    public function rules(): array
    {
        $structureId = $this->currentStructureId();

        return [
            'intervenant_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')
                    ->where('structure_id', $structureId)
                    ->where('type', UserType::Intervenant->value)
                    ->whereNull('deleted_at'),
            ],
            'beneficiary_id' => [
                'required', 'uuid',
                Rule::exists('beneficiaries', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
            'care_plan_id' => [
                'nullable', 'uuid',
                Rule::exists('care_plans', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
            'planned_date' => ['required', 'date'],
            'planned_start_time' => ['nullable', 'date_format:H:i'],
            'planned_end_time' => ['nullable', 'date_format:H:i', 'after:planned_start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'intervenant_id.required' => "L'intervenant est obligatoire.",
            'intervenant_id.exists' => 'Intervenant introuvable dans cette structure.',
            'beneficiary_id.required' => 'Le bénéficiaire est obligatoire.',
            'beneficiary_id.exists' => 'Bénéficiaire introuvable dans cette structure.',
            'planned_date.required' => 'La date planifiée est obligatoire.',
            'planned_end_time.after' => "L'heure de fin doit être postérieure à l'heure de début.",
        ];
    }
}
