<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StartImpersonationRequest;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Platform-admin impersonation — lets a super-admin temporarily adopt the
 * tenant context of any user account for investigation / support purposes.
 *
 * SECURITY INVARIANTS:
 *  - Only users with is_platform_admin === true can start impersonation.
 *  - Cannot impersonate another platform admin.
 *  - Cannot impersonate self.
 *  - Reason is mandatory (HDS / RGPD documentation obligation).
 *  - ImpersonationLog is written BEFORE the session changes so the record
 *    is always attributed to the real platform admin identity.
 *  - stop() is always reachable even if the target user/structure is deleted.
 */
class ImpersonationController extends Controller
{
    /**
     * Begin impersonating the given user.
     * POST /admin/impersonate/{user}
     */
    public function start(StartImpersonationRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is_platform_admin, 403, 'Impossible d\'usurper un administrateur plateforme.');
        abort_if($user->id === $request->user()->id, 403, 'Auto-impersonation interdite.');
        abort_if($user->structure_id === null, 422, 'L\'utilisateur cible n\'est rattaché à aucune structure.');

        $log = ImpersonationLog::create([
            'impersonator_id' => $request->user()->id,
            'impersonated_user_id' => $user->id,
            'structure_id' => $user->structure_id,
            'reason' => $request->validated('reason'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        $request->session()->put('impersonating_as', [
            'log_id' => $log->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'structure_id' => $user->structure_id,
            'structure_name' => $user->structure?->name,
        ]);

        return redirect()->route('dashboard')
            ->with('info', "Vous accédez au compte de {$user->name} — pensez à terminer la session.");
    }

    /**
     * End the active impersonation session and return to the admin panel.
     * POST /admin/impersonate/stop
     */
    public function stop(Request $request): RedirectResponse
    {
        $payload = $request->session()->get('impersonating_as');

        if ($payload !== null) {
            ImpersonationLog::where('id', $payload['log_id'])
                ->whereNull('stopped_at')
                ->update([
                    'stopped_at' => now(),
                ]);

            $request->session()->forget('impersonating_as');
        }

        return redirect()->route('admin.dashboard');
    }
}
