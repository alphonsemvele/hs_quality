<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActionStatus;
use App\Enums\PacSource;
use App\Http\Requests\Pac\CancelPlanRequest;
use App\Http\Requests\Pac\ClosePlanRequest;
use App\Http\Requests\Pac\StoreActionRequest;
use App\Http\Requests\Pac\StorePlanAmeliorationRequest;
use App\Http\Requests\Pac\UpdateActionRequest;
use App\Http\Requests\Pac\UpdatePlanAmeliorationRequest;
use App\Models\ActionAmelioration;
use App\Models\PlanAmelioration;
use App\Services\PlanAmeliorationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Module 6 — Plans d'Amélioration Continue (PAC).
 *
 * Tracks corrective action plans triggered by audits, incidents, QVCT
 * alerts, or beneficiary complaints — with per-action status and
 * progression %.
 */
class PlanAmeliorationController extends Controller
{
    public function __construct(private readonly PlanAmeliorationService $plans) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PlanAmelioration::class);

        $plans = PlanAmelioration::query()
            ->with(['actions' => fn ($q) => $q->where('statut', '!=', ActionStatus::Annulee->value)])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PlanAmelioration $p) {
                $relevantActions = $p->actions;
                $total = $relevantActions->count();
                $done = $relevantActions->where('statut', ActionStatus::Realisee)->count();

                return [
                    'id' => $p->id,
                    'titre' => $p->titre,
                    'source' => $p->source->value,
                    'source_label' => $p->source->label(),
                    'statut' => $p->statut->value,
                    'statut_label' => $p->statut->label(),
                    'echeance' => $p->echeance?->format('Y-m-d'),
                    'responsable' => $p->responsable,
                    'nb_actions' => $total,
                    'nb_actions_realisees' => $done,
                    'progression' => $total === 0 ? 0 : (int) round(($done / $total) * 100),
                ];
            })->all();

        $structureId = currentStructure()?->getKey();

        return Inertia::render('dashboard/plans-amelioration/index', [
            'plans' => $plans,
            'stats' => $structureId
                ? $this->plans->statsForStructure($structureId)
                : ['total' => 0, 'en_cours' => 0, 'termines' => 0, 'taux_completion' => null],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PlanAmelioration::class);

        return Inertia::render('dashboard/plans-amelioration/create', [
            'sources' => array_map(
                fn (PacSource $s) => ['value' => $s->value, 'label' => $s->label()],
                PacSource::cases(),
            ),
        ]);
    }

    public function store(StorePlanAmeliorationRequest $request): RedirectResponse
    {
        $plan = $this->plans->create($request->validated(), $request->user());

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', "Plan d'amélioration créé.");
    }

    public function show(PlanAmelioration $plan): Response
    {
        $this->authorize('view', $plan);

        $plan->load('actions');

        return Inertia::render('dashboard/plans-amelioration/show', [
            'plan' => [
                'id' => $plan->id,
                'titre' => $plan->titre,
                'source' => $plan->source->value,
                'source_label' => $plan->source->label(),
                'source_id' => $plan->source_id,
                'constat' => $plan->constat,
                'statut' => $plan->statut->value,
                'statut_label' => $plan->statut->label(),
                'responsable' => $plan->responsable,
                'echeance' => $plan->echeance?->format('Y-m-d'),
                'progression' => $plan->progression(),
                'is_terminal' => $plan->isTerminal(),
                'closed_at' => $plan->closed_at?->format('Y-m-d H:i'),
                'cancelled_at' => $plan->cancelled_at?->format('Y-m-d H:i'),
                'cancellation_reason' => $plan->cancellation_reason,
                'created_at' => $plan->created_at?->format('Y-m-d H:i'),
                'actions' => $plan->actions->map(fn (ActionAmelioration $a) => [
                    'id' => $a->id,
                    'description' => $a->description,
                    'responsable' => $a->responsable,
                    'echeance' => $a->echeance?->format('Y-m-d'),
                    'statut' => $a->statut->value,
                    'statut_label' => $a->statut->label(),
                    'realise_at' => $a->realise_at?->format('Y-m-d H:i'),
                ])->all(),
            ],
            'can' => [
                'update' => request()->user()?->can('update', $plan) ?? false,
                'close' => request()->user()?->can('close', $plan) ?? false,
                'cancel' => request()->user()?->can('cancel', $plan) ?? false,
            ],
        ]);
    }

    public function edit(PlanAmelioration $plan): Response
    {
        $this->authorize('update', $plan);

        return Inertia::render('dashboard/plans-amelioration/edit', [
            'plan' => [
                'id' => $plan->id,
                'titre' => $plan->titre,
                'source' => $plan->source->value,
                'source_label' => $plan->source->label(),
                'constat' => $plan->constat,
                'responsable' => $plan->responsable,
                'echeance' => $plan->echeance?->format('Y-m-d'),
            ],
        ]);
    }

    public function update(UpdatePlanAmeliorationRequest $request, PlanAmelioration $plan): RedirectResponse
    {
        $this->plans->update($plan, $request->validated());

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Plan mis à jour.');
    }

    public function storeAction(StoreActionRequest $request, PlanAmelioration $plan): RedirectResponse
    {
        $this->plans->addAction($plan, $request->validated());

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Action ajoutée.');
    }

    public function updateAction(
        UpdateActionRequest $request,
        PlanAmelioration $plan,
        ActionAmelioration $action,
    ): RedirectResponse {
        abort_if($action->plan_amelioration_id !== $plan->id, 404);

        $this->plans->updateAction($action, $request->validated());

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Action mise à jour.');
    }

    public function markActionDone(PlanAmelioration $plan, ActionAmelioration $action): RedirectResponse
    {
        $this->authorize('update', $plan);
        abort_if($action->plan_amelioration_id !== $plan->id, 404);

        $this->plans->markActionDone($action);

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Action marquée comme réalisée.');
    }

    public function destroyAction(PlanAmelioration $plan, ActionAmelioration $action): RedirectResponse
    {
        $this->authorize('update', $plan);
        abort_if($action->plan_amelioration_id !== $plan->id, 404);

        $this->plans->deleteAction($action);

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Action supprimée.');
    }

    public function close(ClosePlanRequest $request, PlanAmelioration $plan): RedirectResponse
    {
        $this->plans->close($plan, $request->input('reason'));

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Plan clôturé.');
    }

    public function cancel(CancelPlanRequest $request, PlanAmelioration $plan): RedirectResponse
    {
        $this->plans->cancel($plan, $request->input('reason'));

        return redirect()
            ->route('pac.show', $plan)
            ->with('success', 'Plan annulé.');
    }
}
