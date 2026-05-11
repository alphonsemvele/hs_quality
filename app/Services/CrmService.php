<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StructureTier;
use App\Models\Structure;
use Illuminate\Support\Facades\Log;

/**
 * CRM integration stub — logs events instead of calling HubSpot.
 *
 * HubSpot credentials are not yet provisioned; this stub preserves the
 * interface so SubscriptionController / BillingService can call through
 * without branching. When HubSpot is ready, replace the log lines with
 * the HubSpot PHP SDK calls — callers stay unchanged.
 *
 * Events recorded: Pro/Premium upgrade, subscription cancelled.
 *
 * Spec: PHASE2_PROGRESS.md C5.
 */
class CrmService
{
    public function recordUpgrade(Structure $structure, StructureTier $newTier): void
    {
        Log::info('CRM: structure upgraded', [
            'structure_id' => $structure->id,
            'structure_name' => $structure->name,
            'new_tier' => $newTier->value,
            'billing_email' => $structure->billing_email,
        ]);
    }

    public function recordCancellation(Structure $structure): void
    {
        Log::info('CRM: structure subscription cancelled', [
            'structure_id' => $structure->id,
            'structure_name' => $structure->name,
            'tier_at_cancel' => $structure->tier->value,
        ]);
    }
}
