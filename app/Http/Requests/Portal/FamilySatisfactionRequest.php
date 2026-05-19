<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Http\Requests\BaseFormRequest;

class FamilySatisfactionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // auth is handled by ValidateFamilyToken middleware
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'intervention_id' => ['nullable', 'uuid', 'exists:interventions,id'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
