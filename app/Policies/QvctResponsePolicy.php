<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctResponse;
use App\Models\User;

/**
 * Anonymity-first policy. Individual responses are never user-visible —
 * only aggregates are exposed via QvctCampaignPolicy + dashboard. Even
 * RH cannot inspect a single response (could de-anonymize via metadata
 * correlation: "the only intervenant in team X who answered on date Y").
 *
 * Every method returns false: no read endpoint should ever surface a
 * single QvctResponse. If a future need arises (e.g. anonymized
 * statistical export) it goes through a dedicated aggregation service,
 * not through this policy.
 */
class QvctResponsePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, QvctResponse $response): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        // Submit IS authorized — but goes through QvctCampaignPolicy::respond
        // because the access check is "may this user submit to *this* campaign",
        // not "may this user create a QvctResponse model in the abstract".
        return false;
    }

    public function update(User $user, QvctResponse $response): bool
    {
        // Anonymous responses are immutable. Submit-then-edit would let an
        // operator tie a user to a row by observing a write.
        return false;
    }

    public function delete(User $user, QvctResponse $response): bool
    {
        // Operators can request bulk anonymized deletion via RGPD service;
        // single-row deletion would be deanonymizing.
        return false;
    }
}
