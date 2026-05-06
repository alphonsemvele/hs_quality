<?php

namespace App\Http\Requests\Audits;

use App\Enums\Referentiel;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateAuditRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('audit'));
    }

    public function rules(): array
    {
        return [
            'titre' => ['sometimes', 'required', 'string', 'max:255'],
            'referentiel' => ['sometimes', 'required', Rule::enum(Referentiel::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_audit' => ['nullable', 'date'],
            'auditeur' => ['nullable', 'string', 'max:150'],
        ];
    }
}
