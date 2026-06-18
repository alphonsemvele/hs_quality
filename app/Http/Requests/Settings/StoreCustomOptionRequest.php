<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\CustomOptionField;
use App\Http\Requests\BaseFormRequest;
use App\Models\CustomOption;
use Illuminate\Validation\Rule;

class StoreCustomOptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CustomOption::class);
    }

    public function rules(): array
    {
        $structureId = $this->currentStructureId();

        return [
            'field_key' => ['required', 'string', Rule::enum(CustomOptionField::class)],

            // value is immutable once stored; enforce uniqueness here at creation time.
            'value' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('custom_options')
                    ->where('structure_id', $structureId)
                    ->where('field_key', $this->input('field_key'))
                    ->whereNull('deleted_at'),
            ],

            'label' => ['required', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'field_key.required' => 'Le champ cible est obligatoire.',
            'field_key.enum' => 'Ce champ ne prend pas en charge les options personnalisées.',
            'value.required' => 'La clé de valeur est obligatoire.',
            'value.regex' => 'La clé ne peut contenir que des lettres minuscules, chiffres, tirets et underscores.',
            'value.unique' => 'Cette valeur existe déjà pour ce champ.',
            'label.required' => "L'intitulé est obligatoire.",
            'label.max' => "L'intitulé ne peut pas dépasser 200 caractères.",
        ];
    }
}
