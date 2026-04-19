<?php

namespace App\Http\Resources;

use App\Models\Beneficiary;
use Illuminate\Http\Request;

/**
 * @mixin Beneficiary
 *
 * Dossier médical — extends the summary resource with encrypted
 * health-data fields. Endpoints returning this resource MUST be
 * guarded by the log_sensitive_read middleware so every access is
 * audit-logged per CDC §5.2 "journalisation exhaustive des accès".
 *
 * See: references/audit-logging/read-access-logging.md
 */
class BeneficiaryDossierResource extends BeneficiaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'medical_notes' => $this->medical_notes,
            'allergies' => $this->allergies,
            'medical_history' => $this->medical_history,
            'current_treatments' => $this->current_treatments,
        ]);
    }
}
