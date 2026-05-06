<?php

namespace App\Http\Requests\Pac;

use App\Enums\PacSource;
use App\Http\Requests\BaseFormRequest;
use App\Models\PlanAmelioration;
use Illuminate\Validation\Rule;

class StorePlanAmeliorationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PlanAmelioration::class);
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'source' => ['required', Rule::enum(PacSource::class)],
            'source_id' => ['nullable', 'string', 'max:64'],
            'constat' => ['nullable', 'string', 'max:5000'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'echeance' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => "L'intitulé du plan est obligatoire.",
            'source.required' => "L'origine du plan est obligatoire.",
            'echeance.after_or_equal' => "L'échéance ne peut pas être dans le passé.",
        ];
    }
}
