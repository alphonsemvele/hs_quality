<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseFormRequest;

class UpdateCustomOptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('option'));
    }

    public function rules(): array
    {
        return [
            // value is immutable — excluded from update rules intentionally.
            'label' => ['sometimes', 'required', 'string', 'max:200'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => "L'intitulé est obligatoire.",
            'label.max' => "L'intitulé ne peut pas dépasser 200 caractères.",
        ];
    }
}
