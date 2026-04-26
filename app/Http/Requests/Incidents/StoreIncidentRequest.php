<?php

namespace App\Http\Requests\Incidents;

use App\Enums\CategorieIncident;
use App\Http\Requests\BaseFormRequest;
use App\Models\Incident;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Incident::class);
    }

    public function rules(): array
    {
        $structureId = $this->currentStructureId();

        return [
            // Block A / #56 — client-assigned UUID for offline-first mobile.
            // Optional. When the mobile app creates an incident while
            // offline it generates a UUID locally; on reconnect it sends
            // it here so a network retry of the same POST is idempotent.
            // The service either returns the existing row or creates with
            // this id.
            'id' => ['nullable', 'uuid'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'categorie' => ['required', Rule::enum(CategorieIncident::class)],
            'description' => ['required', 'string', 'max:5000'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'avec_deces' => ['boolean'],
            'avec_hospitalisation' => ['boolean'],
            'avec_blessure_physique' => ['boolean'],
            'beneficiary_id' => [
                'nullable', 'uuid',
                Rule::exists('beneficiaries', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
            'intervention_id' => [
                'nullable', 'uuid',
                Rule::exists('interventions', 'id')
                    ->where('structure_id', $structureId)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_at.required' => "La date et l'heure de l'incident sont obligatoires.",
            'occurred_at.before_or_equal' => "La date de l'incident ne peut pas être dans le futur.",
            'categorie.required' => "La catégorie de l'incident est obligatoire.",
            'description.required' => 'La description est obligatoire.',
        ];
    }
}
