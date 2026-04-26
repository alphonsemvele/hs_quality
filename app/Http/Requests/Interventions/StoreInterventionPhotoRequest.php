<?php

namespace App\Http\Requests\Interventions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;

class StoreInterventionPhotoRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        /** @var Intervention $intervention */
        $intervention = $this->route('intervention');

        return $this->user()->can('update', $intervention);
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'La photo est obligatoire.',
            'photo.mimes' => 'Seuls les formats JPEG, PNG et WebP sont acceptés.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ];
    }
}
