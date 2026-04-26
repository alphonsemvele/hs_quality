<?php

namespace App\Http\Requests\Incidents;

use App\Http\Requests\BaseFormRequest;

class UpdateIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:5000'],
            'lieu' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avec_hospitalisation' => ['sometimes', 'boolean'],
            'avec_blessure_physique' => ['sometimes', 'boolean'],
        ];
    }
}
