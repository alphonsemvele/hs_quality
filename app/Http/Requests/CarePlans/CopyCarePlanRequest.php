<?php

namespace App\Http\Requests\CarePlans;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CopyCarePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('copyTemplate', $this->route('carePlan'));
    }

    public function rules(): array
    {
        return [
            'target_beneficiary_id' => [
                'required',
                'uuid',
                Rule::exists('beneficiaries', 'id')->where('structure_id', $this->currentStructureId()),
            ],
            'title' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_beneficiary_id.required' => 'Le bénéficiaire cible est obligatoire.',
            'target_beneficiary_id.exists' => 'Bénéficiaire cible introuvable dans votre structure.',
            'title.required' => 'Un titre pour le nouveau plan est obligatoire.',
        ];
    }
}
