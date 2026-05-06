<?php

namespace App\Http\Requests\Pac;

use App\Http\Requests\BaseFormRequest;

class UpdatePlanAmeliorationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plan'));
    }

    public function rules(): array
    {
        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'constat' => ['nullable', 'string', 'max:5000'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'echeance' => ['nullable', 'date'],
        ];
    }
}
