<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Structure;

/**
 * Pennant feature flag: per-structure kill switch for the QVCT
 * weak-signal mail fan-out (NotifyReferentRhJob → référent RH +
 * dirigeant + référent qualité).
 *
 * Default: on. Signal rows + cartography + audit log persist
 * regardless of this flag — only the outbound mail is gated, so
 * disabling it is safe (does not lose data, only suppresses
 * notifications).
 *
 * Why a flag and not config: we want per-structure control. A pilot
 * tenant reporting alert fatigue can be muted without code changes
 * via Feature::for($structure)->deactivate(QvctWeakSignalAlerts::class).
 *
 * Scope: Structure (multi-tenant). Always evaluate via
 *   Feature::for($structure)->active(QvctWeakSignalAlerts::class)
 * never via the implicit user scope, otherwise queue workers (which
 * have no auth user) will silently misroute.
 */
class QvctWeakSignalAlerts
{
    public function resolve(Structure $structure): bool
    {
        return true;
    }
}
