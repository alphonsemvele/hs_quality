<?php

namespace App\Http\Requests\Audits;

use App\Enums\EcartGravite;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreEcartRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // The audit's `execute` ability gates the writing of écarts.
        return $this->user()->can('execute', $this->route('audit'));
    }

    public function rules(): array
    {
        return [
            'critere' => ['required', 'string', 'max:255'],
            'constat' => ['required', 'string', 'max:5000'],
            'gravite' => ['required', Rule::enum(EcartGravite::class)],
            'action_corrective' => ['nullable', 'string', 'max:2000'],
            // When the écart is gravite=majeur|critique, the operator can opt
            // to auto-create a PAC entry seeded with this écart.
            'create_pac' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'critere.required' => 'Le critère évalué est obligatoire.',
            'constat.required' => 'Le constat est obligatoire.',
            'gravite.required' => 'La gravité est obligatoire.',
        ];
    }
}
