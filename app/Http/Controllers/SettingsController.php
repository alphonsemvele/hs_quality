<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant-level settings (structure profile, contact, compliance display).
 *
 * Most structure fields (code, name, siret, type, tier) are managed via the
 * platform admin console for traceability. The tenant dirigeant can only
 * update soft fields here (billing email, locale preferences). Hard changes
 * require a support ticket so they go through the audit trail.
 */
class SettingsController extends Controller
{
    public function structure(Request $request): Response
    {
        $structure = currentStructure();
        abort_unless($structure, 404);

        return Inertia::render('dashboard/settings/structure', [
            'structure' => [
                'id' => $structure->id,
                'code' => $structure->code,
                'name' => $structure->name,
                'type' => $structure->type instanceof \BackedEnum ? $structure->type->value : (string) $structure->type,
                'type_label' => $structure->type?->label() ?? '—',
                'siret' => $structure->siret,
                'address' => $structure->address,
                'tier' => $structure->tier instanceof \BackedEnum ? $structure->tier->value : (string) $structure->tier,
                'tier_label' => $structure->tier?->label() ?? '—',
                'status' => $structure->status instanceof \BackedEnum ? $structure->status->value : (string) $structure->status,
                'billing_email' => $structure->billing_email,
                'trial_ends_at' => $structure->trial_ends_at?->toIso8601String(),
                'created_at' => $structure->created_at?->toIso8601String(),
            ],
            'compliance' => [
                'hosting' => 'HDS — AWS Paris (eu-west-3)',
                'encryption_at_rest' => 'AES-256 — clés gérées via AWS KMS',
                'encryption_in_transit' => 'TLS 1.3 minimum',
                'data_retention' => '10 ans (interventions, incidents) — RGPD Art. 5',
                'backup_frequency' => 'Snapshots PostgreSQL toutes les 6h, point-in-time recovery 35 jours',
                'audit_log' => 'Activé — chaque action sur les données personnelles est tracée',
            ],
        ]);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $structure = currentStructure();
        abort_unless($structure, 404);

        $validated = $request->validate([
            'billing_email' => ['required', 'email', 'max:255'],
        ]);

        $structure->update($validated);

        return back()->with('success', 'Email de facturation mis à jour.');
    }
}
