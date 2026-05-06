<?php

namespace App\Http\Requests\Audits;

use App\Enums\Referentiel;
use App\Http\Requests\BaseFormRequest;
use App\Models\QualityAudit;
use Illuminate\Validation\Rule;

class StoreAuditRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', QualityAudit::class);
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'max:255'],
            'referentiel' => ['required', Rule::enum(Referentiel::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_audit' => ['nullable', 'date'],
            'auditeur' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => "L'intitulé de l'audit est obligatoire.",
            'referentiel.required' => 'Le référentiel est obligatoire.',
        ];
    }
}
