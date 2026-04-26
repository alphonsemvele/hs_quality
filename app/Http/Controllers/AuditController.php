<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M6 — HAS / AFNOR / ISO 9001 audit grids and conformity scoring.
 *
 * Currently a placeholder. Previously returned hardcoded sample data
 * including named individuals — that constituted an RGPD breach for any
 * authenticated user (Wave 1 / C1 in the security sweep). Until the
 * domain ships in Phase 2 Month 6, this controller renders a "coming soon"
 * page so the sidebar entry remains discoverable without leaking data.
 */
class AuditController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'M6 Audits & Conformité',
            'feature_label' => 'Audits & Conformité',
            'description' => 'Préparation aux évaluations HAS, audits AFNOR NF X50-056 et ISO 9001, '
                .'grilles personnalisables, scoring automatique et génération du PAC à partir des écarts.',
            'eta' => 'Phase 2 — Mois 6 (T3 2026)',
            'tier_required' => 'pro',
        ]);
    }
}
