<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ImpersonationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for reading the `impersonating_as` session payload.
 *
 * Centralizes the 4-hour hard timeout (HDS / RGPD: an admin must not be able
 * to leave an impersonation session open indefinitely) so every call site —
 * middleware, policies, audit stamping, route binding — enforces it
 * identically instead of re-deriving expiry logic in each place.
 *
 * @phpstan-type ImpersonationPayload array{log_id: int, user_id: int, user_name: string, structure_id: string, structure_name: ?string, started_at: string}
 */
class ImpersonationSession
{
    public const MAX_DURATION_MINUTES = 240;

    /**
     * @return ImpersonationPayload|null
     */
    public static function current(?Request $request = null): ?array
    {
        $request ??= request();

        if (! $request->hasSession()) {
            return null;
        }

        $payload = $request->session()->get('impersonating_as');

        if ($payload === null) {
            return null;
        }

        if (self::isExpired($payload)) {
            self::clear($request, $payload);

            return null;
        }

        return $payload;
    }

    /**
     * @param  ImpersonationPayload  $payload
     */
    private static function isExpired(array $payload): bool
    {
        $startedAt = $payload['started_at'] ?? null;

        if ($startedAt === null) {
            return false;
        }

        return Carbon::parse($startedAt)->diffInMinutes(now()) >= self::MAX_DURATION_MINUTES;
    }

    /**
     * @param  ImpersonationPayload  $payload
     */
    private static function clear(Request $request, array $payload): void
    {
        ImpersonationLog::where('id', $payload['log_id'])
            ->whereNull('stopped_at')
            ->update(['stopped_at' => now()]);

        $request->session()->forget('impersonating_as');
    }
}
