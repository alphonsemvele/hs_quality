<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StructureTier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 — Inertia surface for the subscription management UI.
 *
 * Write paths (subscribe / cancel / change plan) go through the existing
 * `Api\V1\SubscriptionController` to keep web + mobile parity. This
 * controller only renders the view and demo invoices.
 */
class BillingController extends Controller
{
    public function show(): Response
    {
        $structure = currentStructure();
        $demo = config('app.env') === 'local';

        $currentTier = $structure?->tier ?? StructureTier::Essential;
        $userCount = $demo ? 12 : 0;

        return Inertia::render('dashboard/billing/index', [
            'currentTier' => $currentTier->value,
            'currentTierLabel' => $currentTier->label(),
            'userCount' => $userCount,
            'nextInvoiceAmount' => $userCount * $currentTier->monthlyPricePerUser(),
            'nextInvoiceDate' => $demo ? '2026-06-01' : null,
            'paymentMethod' => $demo ? ['brand' => 'Visa', 'last4' => '4242', 'expires' => '12/2027'] : null,
            'tiers' => $this->tiers($currentTier),
            'invoices' => $demo ? $this->demoInvoices() : [],
            'trialEndsAt' => null,
        ]);
    }

    public function changePlan(): RedirectResponse
    {
        return back()->with('info', 'Changement de plan en cours de développement — utilisez l\'API Stripe.');
    }

    public function cancel(): RedirectResponse
    {
        return back()->with('success', 'Abonnement programmé pour résiliation à la fin de la période (demo).');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tiers(StructureTier $current): array
    {
        return [
            [
                'key' => StructureTier::Essential->value,
                'label' => StructureTier::Essential->label(),
                'price_per_user' => StructureTier::Essential->monthlyPricePerUser(),
                'is_current' => $current === StructureTier::Essential,
                'tagline' => 'Pour démarrer la mise en conformité',
                'features' => [
                    'Traçabilité interventions M1',
                    'Déclaration & gestion d\'incidents M3',
                    'Tableau de bord opérationnel',
                    'Application mobile intervenants',
                    'Stockage HDS France',
                ],
            ],
            [
                'key' => StructureTier::Pro->value,
                'label' => StructureTier::Pro->label(),
                'price_per_user' => StructureTier::Pro->monthlyPricePerUser(),
                'is_current' => $current === StructureTier::Pro,
                'is_recommended' => true,
                'tagline' => 'Le standard pour SAAD / SSIAD',
                'features' => [
                    'Tout Essentiel',
                    'Audits HAS / ISO / AFNOR',
                    'Plans d\'amélioration (PAC)',
                    'Module QVCT — baromètres + signaux faibles',
                    'Communication interne (messages, news, docs)',
                    'Formations & habilitations',
                ],
            ],
            [
                'key' => StructureTier::Premium->value,
                'label' => StructureTier::Premium->label(),
                'price_per_user' => StructureTier::Premium->monthlyPricePerUser(),
                'is_current' => $current === StructureTier::Premium,
                'tagline' => 'Pour structures multi-sites & exigeantes',
                'features' => [
                    'Tout Pro',
                    'Portail bénéficiaires & familles',
                    'IA prédictive — burnout, autonomie',
                    'Benchmark anonymisé secteur',
                    'API ouverte + webhooks',
                    'Support dédié + SLA 99,9 %',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoInvoices(): array
    {
        return [
            ['id' => 'in_2026_05', 'number' => 'FAC-2026-05-001', 'date' => '2026-05-01', 'amount' => 180, 'status' => 'paid', 'status_label' => 'Payée', 'pdf_url' => '#'],
            ['id' => 'in_2026_04', 'number' => 'FAC-2026-04-001', 'date' => '2026-04-01', 'amount' => 180, 'status' => 'paid', 'status_label' => 'Payée', 'pdf_url' => '#'],
            ['id' => 'in_2026_03', 'number' => 'FAC-2026-03-001', 'date' => '2026-03-01', 'amount' => 165, 'status' => 'paid', 'status_label' => 'Payée', 'pdf_url' => '#'],
            ['id' => 'in_2026_02', 'number' => 'FAC-2026-02-001', 'date' => '2026-02-01', 'amount' => 165, 'status' => 'paid', 'status_label' => 'Payée', 'pdf_url' => '#'],
            ['id' => 'in_2026_01', 'number' => 'FAC-2026-01-001', 'date' => '2026-01-01', 'amount' => 150, 'status' => 'paid', 'status_label' => 'Payée', 'pdf_url' => '#'],
        ];
    }
}
