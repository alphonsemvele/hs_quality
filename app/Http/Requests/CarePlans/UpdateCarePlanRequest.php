<?php

namespace App\Http\Requests\CarePlans;

use App\Enums\CarePlanStatus;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCarePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('care_plan'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'objectives' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', new Enum(CarePlanStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du plan est obligatoire.',
            'end_date.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }
}
