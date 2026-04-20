<?php

namespace App\Http\Requests\Interventions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;

class CancelInterventionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $intervention = $this->route('intervention');

        return $intervention instanceof Intervention
            && $this->user()->can('cancel', $intervention);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => "La raison de l'annulation est obligatoire.",
            'cancellation_reason.max' => 'La raison ne peut pas dépasser :max caractères.',
        ];
    }
}
