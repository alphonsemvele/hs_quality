<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'structure_id' => $this->structure_id,
            'declared_by' => $this->declared_by,
            'assigned_to' => $this->assigned_to,
            'beneficiary_id' => $this->beneficiary_id,
            'intervention_id' => $this->intervention_id,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'categorie' => $this->categorie?->value,
            'gravite' => $this->gravite?->value,
            'statut' => $this->statut?->value,
            'description' => $this->description,
            'lieu' => $this->lieu,
            'avec_deces' => $this->avec_deces,
            'avec_hospitalisation' => $this->avec_hospitalisation,
            'avec_blessure_physique' => $this->avec_blessure_physique,
            'analyse_causes' => $this->analyse_causes,
            'requires_ars_notification' => $this->gravite?->requiresARSNotification(),
            'closed_at' => $this->closed_at?->toISOString(),
            'notifie_responsable_at' => $this->notifie_responsable_at?->toISOString(),
            'notifie_ars_at' => $this->notifie_ars_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
