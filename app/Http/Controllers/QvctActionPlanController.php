<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QvctActionPlanItemStatus;
use App\Http\Requests\Qvct\StoreQvctActionPlanItemRequest;
use App\Http\Requests\Qvct\StoreQvctActionPlanRequest;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Services\ActionPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Web surface for QVCT action plans. Mirrors Api\V1\QvctActionPlanController
 * (same service, same Form Requests, same policy gates) but returns Inertia
 * responses instead of JSON. Per CLAUDE.md DRY rule, business logic lives
 * exclusively in ActionPlanService.
 */
class QvctActionPlanController extends Controller
{
    public function __construct(private readonly ActionPlanService $service) {}

    public function create(): Response
    {
        $this->authorize('create', QvctActionPlan::class);

        return Inertia::render('dashboard/qvct/action-plans/create');
    }

    public function store(StoreQvctActionPlanRequest $request): RedirectResponse
    {
        $plan = $this->service->draft($request->user(), $request->validated());

        return redirect()->route('qvct.action-plans.show', $plan)
            ->with('success', 'Plan d\'action créé en brouillon.');
    }

    public function show(QvctActionPlan $plan): Response
    {
        $this->authorize('view', $plan);

        $plan->load([
            'items' => fn ($q) => $q->orderBy('created_at'),
            'items.responsibleUser:id,first_name,last_name',
            'createdBy:id,first_name,last_name',
        ]);

        return Inertia::render('dashboard/qvct/action-plans/show', [
            'plan' => [
                'id' => $plan->id,
                'title' => $plan->title,
                'description' => $plan->description,
                'target_quarter' => $plan->target_quarter,
                'status' => $plan->status->value,
                'status_label' => $plan->status->label(),
                'published_at' => $plan->published_at?->toIso8601String(),
                'closed_at' => $plan->closed_at?->toIso8601String(),
                'created_at' => $plan->created_at?->toIso8601String(),
                'created_by' => $plan->createdBy
                    ? trim($plan->createdBy->first_name.' '.$plan->createdBy->last_name)
                    : null,
                'items' => $plan->items->map(fn (QvctActionPlanItem $i): array => [
                    'id' => $i->id,
                    'title' => $i->title,
                    'description' => $i->description,
                    'responsible' => $i->responsibleUser
                        ? trim($i->responsibleUser->first_name.' '.$i->responsibleUser->last_name)
                        : null,
                    'due_date' => $i->due_date?->toDateString(),
                    'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
                    'status_label' => $i->status instanceof QvctActionPlanItemStatus ? $i->status->label() : (string) $i->status,
                    'impact_measurement_target' => $i->impact_measurement_target,
                    'impact_measurement_actual' => $i->impact_measurement_actual,
                ])->all(),
            ],
        ]);
    }

    public function storeItem(StoreQvctActionPlanItemRequest $request, QvctActionPlan $plan): RedirectResponse
    {
        $this->service->addItem($plan, $request->validated());

        return back()->with('success', 'Action ajoutée au plan.');
    }

    public function publish(QvctActionPlan $plan, Request $request): RedirectResponse
    {
        $this->authorize('update', $plan);

        $this->service->publish($plan, $request->user());

        return back()->with('success', 'Plan d\'action publié.');
    }

    public function close(QvctActionPlan $plan, Request $request): RedirectResponse
    {
        $this->authorize('update', $plan);

        $this->service->close($plan, $request->user());

        return back()->with('success', 'Plan d\'action clôturé.');
    }

    public function updateItemStatus(Request $request, QvctActionPlanItem $item): RedirectResponse
    {
        $this->authorize('update', $item->actionPlan);

        $status = (string) $request->input('status', '');
        $allowed = array_map(fn (QvctActionPlanItemStatus $s) => $s->value, QvctActionPlanItemStatus::cases());
        if (! in_array($status, $allowed, true)) {
            throw new HttpException(422, 'Statut invalide.');
        }

        $this->service->updateItemStatus($item, $status);

        return back()->with('success', 'Statut de l\'action mis à jour.');
    }
}
