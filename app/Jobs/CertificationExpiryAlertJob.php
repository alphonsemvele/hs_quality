<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Certification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Daily certification-expiry sweep. Spec: PHASE2_PROGRESS.md M5.9.
 *
 * For every non-soft-deleted certification, computes the most-recent
 * expiry window the cert has crossed (T-90, T-30, T-7, expired) and
 * fires a single alert per window per cert. Idempotent: re-running
 * the job on the same day is a no-op until the cert moves to the next
 * window.
 *
 * Windowing rules:
 *   days  > 90  → no alert window applies
 *   90 ≥ d > 30 → window 'T-90'
 *   30 ≥ d >  7 → window 'T-30'
 *    7 ≥ d ≥ 0 → window 'T-7'
 *           d < 0 → window 'expired'
 *
 * Each cert stores `last_alert_window`; the job advances it forward
 * monotonically. A cert that already has window 'T-30' will not
 * re-fire 'T-90' even if the date math somehow regressed (clock
 * skew, manual expires_at edit) — only forward transitions trigger
 * alerts.
 *
 * Tenant context: the job iterates ALL structures in one pass because
 * laravel-auditing already records the structure_id in audit logs;
 * the real notification fan-out (Phase 2 mail/SMS slice) is
 * structure-scoped via the Certification's structure_id.
 */
class CertificationExpiryAlertJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Window names ordered from earliest (T-90) to latest (expired). */
    public const WINDOWS = ['T-90', 'T-30', 'T-7', 'expired'];

    public function handle(): void
    {
        Certification::query()
            ->withoutGlobalScopes()
            ->chunkById(500, function ($certs): void {
                foreach ($certs as $cert) {
                    $this->processCert($cert);
                }
            });
    }

    private function processCert(Certification $cert): void
    {
        $window = self::windowFor($cert->daysUntilExpiry());

        if ($window === null) {
            return; // > 90 days out, nothing to do
        }

        // Idempotent: only advance forward.
        if (! self::shouldAdvance($cert->last_alert_window, $window)) {
            return;
        }

        $cert->update([
            'last_alert_window' => $window,
            'last_alerted_at' => now(),
        ]);

        Log::info('Certification expiry alert', [
            'certification_id' => $cert->id,
            'structure_id' => $cert->structure_id,
            'user_id' => $cert->user_id,
            'type' => $cert->type,
            'window' => $window,
            'expires_at' => optional($cert->expires_at)->toDateString(),
        ]);

        // TODO Phase 2 / M5 follow-up: dispatch a Notification (mail
        // + in-app badge) to the user, the responsable formation,
        // and the user's coordinateur. The log line above is the
        // observable trace until that lands.
    }

    /**
     * Map a days-until-expiry value to its window, or null if no
     * window applies (i.e. > 90 days out).
     */
    public static function windowFor(int $daysUntilExpiry): ?string
    {
        return match (true) {
            $daysUntilExpiry < 0 => 'expired',
            $daysUntilExpiry <= 7 => 'T-7',
            $daysUntilExpiry <= 30 => 'T-30',
            $daysUntilExpiry <= 90 => 'T-90',
            default => null,
        };
    }

    /**
     * True when the new window is strictly later than the previous one.
     * A null `previous` (never alerted) always advances.
     */
    public static function shouldAdvance(?string $previous, string $next): bool
    {
        if ($previous === null) {
            return true;
        }

        $prevIdx = array_search($previous, self::WINDOWS, strict: true);
        $nextIdx = array_search($next, self::WINDOWS, strict: true);

        if ($prevIdx === false || $nextIdx === false) {
            return true; // unknown previous → fire (defensive)
        }

        return $nextIdx > $prevIdx;
    }
}
