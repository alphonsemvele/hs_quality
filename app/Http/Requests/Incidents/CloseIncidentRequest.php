<?php

namespace App\Http\Requests\Incidents;

use App\Http\Requests\BaseFormRequest;

class CloseIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'closing_note' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_note.required' => 'Une note de clôture est obligatoire.',
            'closing_note.min' => 'La note de clôture doit comporter au moins 10 caractères.',
        ];
    }
}
