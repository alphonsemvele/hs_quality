<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StructureTier;
use App\Models\User;
use App\Services\BillingService;
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
        $currentTier = $structure?->tier ?? StructureTier::Essential;
        $userCount = $structure ? User::query()->where('structure_id', $structure->id)->count() : 0;

        // Cashier subscription — null when Stripe isn't configured locally.
        $subscription = $structure?->subscription(BillingService::SUBSCRIPTION_TYPE);
        $paymentMethod = null;
        $nextInvoiceDate = null;
        $invoices = [];
        $trialEndsAt = null;

        if ($subscription !== null) {
            $trialEndsAt = $subscription->trial_ends_at?->toDateString();

            try {
                $pm = $structure->defaultPaymentMethod();
                if ($pm) {
                    $paymentMethod = [
                        'brand' => $pm->card?->brand ?? '—',
                        'last4' => $pm->card?->last4 ?? '—',
                        'expires' => sprintf('%02d/%d', $pm->card?->exp_month ?? 0, $pm->card?->exp_year ?? 0),
                    ];
                }

                $invoices = $structure->invoices()->map(fn ($inv) => [
                    'id' => $inv->id,
                    'number' => $inv->number ?? $inv->id,
                    'date' => $inv->date()->toDateString(),
                    'amount' => $inv->rawTotal() / 100,
                    'status' => $inv->paid ? 'paid' : 'open',
                    'status_label' => $inv->paid ? 'Payée' : 'En attente',
                    'pdf_url' => $inv->invoice_pdf ?? '#',
                ])->values()->all();

                $upcomingInvoice = $structure->upcomingInvoice();
                $nextInvoiceDate = $upcomingInvoice?->date()->toDateString();
            } catch (\Exception) {
                // Stripe not reachable in this environment — invoices stay empty.
            }
        }

        return Inertia::render('dashboard/billing/index', [
            'currentTier' => $currentTier->value,
            'currentTierLabel' => $currentTier->label(),
            'userCount' => $userCount,
            'nextInvoiceAmount' => $userCount * $currentTier->monthlyPricePerUser(),
            'nextInvoiceDate' => $nextInvoiceDate,
            'paymentMethod' => $paymentMethod,
            'tiers' => $this->tiers($currentTier),
            'invoices' => $invoices,
            'trialEndsAt' => $trialEndsAt,
        ]);
    }

    public function changePlan(BillingService $service): RedirectResponse
    {
        // Full plan-change flow requires a Stripe payment-method on the
        // structure (handled in the mobile API SubscriptionController).
        // Web surface shows the billing page; actual subscribe call goes
        // through POST /api/v1/billing/subscribe with a Stripe token.
        return back()->with('info', 'Pour changer de plan, contactez votre référent ou utilisez l\'API Stripe.');
    }

    public function cancel(BillingService $service): RedirectResponse
    {
        $structure = currentStructure();
        $subscription = $structure?->subscription(BillingService::SUBSCRIPTION_TYPE);

        if ($subscription === null || $subscription->canceled()) {
            return back()->with('info', 'Aucun abonnement actif à résilier.');
        }

        try {
            $service->cancel($structure);

            return back()->with('success', 'Abonnement programmé pour résiliation à la fin de la période.');
        } catch (\Exception $e) {
            return back()->with('error', 'Impossible d\'annuler l\'abonnement : '.$e->getMessage());
        }
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
}
