<?php

namespace App\Services;

use App\Auditing\TenantAwareAudit;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Beneficiary lifecycle operations. Called from both Inertia and API
 * controllers so business logic lives in one place (DRY at the service
 * layer — references/conventions/dry.md).
 *
 * Every mutation is wrapped in DB::transaction() so the write + side
 * effects (audit entries, future notifications) commit atomically.
 */
class BeneficiaryService
{
    public function create(array $data): Beneficiary
    {
        return DB::transaction(function () use ($data): Beneficiary {
            // structure_id is auto-populated by BelongsToStructure::creating()
            // from currentStructure() — callers don't need to set it.
            return Beneficiary::create($data);
        });
    }

    public function update(Beneficiary $beneficiary, array $data): Beneficiary
    {
        return DB::transaction(function () use ($beneficiary, $data): Beneficiary {
            $beneficiary->update($data);

            return $beneficiary->fresh();
        });
    }

    /**
     * RGPD Article 17 erasure.
     *
     * Strategy: keep the row (interventions, incidents, audit logs reference
     * it — business records with retention obligations) and anonymize the
     * personal data fields. See:
     *   references/audit-logging/gdpr-erasure.md
     *   references/compliance/rgpd-patterns.md
     *
     * Requires step-up MFA at the route level + rgpd.erasure.execute
     * permission at the Policy level.
     */
    public function anonymize(Beneficiary $beneficiary, string $requestedBy, string $reason): Beneficiary
    {
        return DB::transaction(function () use ($beneficiary, $requestedBy, $reason): Beneficiary {
            $beneficiaryId = $beneficiary->id;

            $beneficiary->update([
                'first_name' => '',
                'last_name' => 'Bénéficiaire supprimé',
                'address' => null,
                'postal_code' => null,
                'city' => null,
                'phone' => null,
                'email' => null,
                'marital_status' => null,
                'primary_doctor' => null,
                'primary_doctor_phone' => null,
                'emergency_contact_name' => null,
                'emergency_contact_phone' => null,
                'emergency_contact_relationship' => null,
                'medical_notes' => null,
                'allergies' => null,
                'medical_history' => null,
                'current_treatments' => null,
                'erased_at' => now(),
            ]);

            // Meta-audit of the erasure itself — kept forever as RGPD
            // compliance evidence.
            TenantAwareAudit::create([
                'user_type' => User::class,
                'user_id' => auth()->id(),
                'event' => 'rgpd_erasure',
                'auditable_type' => Beneficiary::class,
                'auditable_id' => $beneficiaryId,
                'new_values' => json_encode([
                    'requested_by' => $requestedBy,
                    'reason' => $reason,
                    'erased_at' => now()->toIso8601String(),
                ]),
                'tags' => 'rgpd,erasure',
            ]);

            return $beneficiary->fresh();
        });
    }
}
