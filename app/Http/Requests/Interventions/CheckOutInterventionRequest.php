<?php

namespace App\Http\Requests\Interventions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;

class CheckOutInterventionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $intervention = $this->route('intervention');

        return $intervention instanceof Intervention
            && $this->user()->can('checkOut', $intervention);
    }

    public function rules(): array
    {
        return [
            'report_text' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_text.max' => 'Le compte-rendu ne peut pas dépasser :max caractères.',
        ];
    }
}
