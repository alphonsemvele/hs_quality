<?php

namespace App\Http\Requests\Incidents;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreActionCorrectiveRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('incident'));
    }

    public function rules(): array
    {
        $structureId = $this->currentStructureId();

        return [
            'description' => ['required', 'string', 'max:2000'],
            'echeance' => ['nullable', 'date', 'after_or_equal:today'],
            'responsable_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => "La description de l'action corrective est obligatoire.",
            'echeance.after_or_equal' => "L'échéance ne peut pas être dans le passé.",
        ];
    }
}
