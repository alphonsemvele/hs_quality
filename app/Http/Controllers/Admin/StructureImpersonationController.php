<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Structure;
use App\Services\SuperAdminImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Start / stop a "view as dirigeant" impersonation session for a platform
 * operator. The `super_admin` middleware is the outer gate (already applied
 * by the route group) — this controller only owns the session state machine
 * and the audit logging side-effect.
 */
final class StructureImpersonationController extends Controller
{
    public function __construct(
        private readonly SuperAdminImpersonationService $impersonation,
    ) {}

    /**
     * Bind the structure to the current super-admin's session and redirect
     * into the tenant dashboard so the bandeau + menu switch immediately.
     */
    public function start(Structure $structure): RedirectResponse
    {
        $user = request()->user();

        $this->impersonation->start($structure);

        Log::channel(config('logging.default'))->info('superadmin.impersonation.start', [
            'admin_id' => $user?->getKey(),
            'admin_email' => $user?->email,
            'structure_id' => (string) $structure->getKey(),
            'structure_name' => $structure->name,
            'ip' => request()->ip(),
        ]);

        return redirect()->intended('/dashboard')
            ->with(
                'warning',
                sprintf(
                    'Vous agissez désormais en tant que dirigeant de "%s". Toutes les actions sont auditées sous votre identité de superadmin.',
                    $structure->name,
                ),
            );
    }

    /**
     * Exit the impersonation session and bounce back to the structures
     * listing so the operator's next step is obvious.
     */
    public function stop(): RedirectResponse
    {
        $user = request()->user();
        $structureId = $this->impersonation->structureId();

        $this->impersonation->stop();

        Log::channel(config('logging.default'))->info('superadmin.impersonation.stop', [
            'admin_id' => $user?->getKey(),
            'admin_email' => $user?->email,
            'structure_id' => $structureId,
            'ip' => request()->ip(),
        ]);

        return redirect()->route('admin.structures.index')
            ->with('success', 'Vous êtes sorti du mode dirigeant.');
    }
}
