<?php

namespace App\Http\Resources;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Beneficiary
 *
 * Shape a Beneficiary for Inertia props and (later) API responses. Sensitive
 * health-data fields (medical_notes, allergies, medical_history,
 * current_treatments) are intentionally OMITTED from the list/summary shape
 * and only exposed via the dossier endpoint which is log_sensitive_read
 * guarded. See: references/audit-logging/read-access-logging.md
 */
class BeneficiaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'initials' => $this->initials(),

            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),

            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,

            'marital_status' => $this->marital_status,
            'gir' => $this->gir,

            'primary_doctor' => $this->primary_doctor,
            'primary_doctor_phone' => $this->primary_doctor_phone,

            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,

            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'admitted_at' => $this->admitted_at?->toDateString(),
            'exited_at' => $this->exited_at?->toDateString(),
            'exit_reason' => $this->exit_reason,

            'is_erased' => $this->isErased(),
            'erased_at' => $this->erased_at?->toIso8601String(),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
