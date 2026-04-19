<?php

namespace App\Http\Requests\CarePlans;

use App\Http\Requests\BaseFormRequest;
use App\Models\CarePlan;
use Illuminate\Validation\Rule;

class StoreCarePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CarePlan::class);
    }

    public function rules(): array
    {
        return [
            'beneficiary_id' => [
                'required',
                'uuid',
                Rule::exists('beneficiaries', 'id')->where('structure_id', $this->currentStructureId()),
            ],
            'title' => ['required', 'string', 'max:200'],
            'objectives' => ['nullable', 'string', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'beneficiary_id.required' => 'Le bénéficiaire est obligatoire.',
            'beneficiary_id.exists' => 'Bénéficiaire introuvable dans votre structure.',
            'title.required' => 'Le titre du plan est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
