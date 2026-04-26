<?php

namespace App\Http\Requests\Incidents;

use App\Http\Requests\BaseFormRequest;

class AnalyseIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('analyse', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'analyse_causes' => ['required', 'string', 'min:20', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'analyse_causes.required' => "L'analyse des causes (5 Pourquoi) est obligatoire.",
            'analyse_causes.min' => "L'analyse doit comporter au moins 20 caractères.",
        ];
    }
}
