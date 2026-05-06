<?php

namespace App\Http\Requests\Pac;

use App\Http\Requests\BaseFormRequest;

class StoreActionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plan'));
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:2000'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'echeance' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => "La description de l'action est obligatoire.",
        ];
    }
}
