<?php

namespace App\Http\Requests\Incidents;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class AssignIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('incident'));
    }

    public function rules(): array
    {
        $structureId = $this->currentStructureId();

        return [
            'coordinateur_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'coordinateur_id.exists' => 'Coordinateur introuvable dans cette structure.',
        ];
    }
}
