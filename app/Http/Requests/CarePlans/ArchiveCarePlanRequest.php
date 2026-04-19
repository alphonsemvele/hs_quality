<?php

namespace App\Http\Requests\CarePlans;

use App\Http\Requests\BaseFormRequest;

class ArchiveCarePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('archive', $this->route('carePlan'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Une raison d\'archivage est obligatoire.',
            'reason.min' => 'La raison doit contenir au moins :min caractères.',
        ];
    }
}
