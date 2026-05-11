<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QvctActionPlanItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qvct\StoreQvctActionPlanItemRequest;
use App\Http\Requests\Qvct\StoreQvctActionPlanRequest;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Services\ActionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QvctActionPlanController extends Controller
{
    public function __construct(private readonly ActionPlanService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctActionPlan::class);

        $query = QvctActionPlan::query()
            ->with(['items', 'createdBy:id,first_name,last_name'])
            ->orderByDesc('created_at');

        // Non-RH callers never see drafts.
        if (! $request->user()->hasPermissionTo('qvct.action_plan.update')) {
            $query->whereIn('status', ['published', 'closed']);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(StoreQvctActionPlanRequest $request): JsonResponse
    {
        $plan = $this->service->draft($request->user(), $request->validated());

        return response()->json($plan, 201);
    }

    public function show(QvctActionPlan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        $plan->load(['items.responsibleUser:id,first_name,last_name']);

        return response()->json($plan);
    }

    public function publish(Request $request, QvctActionPlan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        return response()->json($this->service->publish($plan, $request->user()));
    }

    public function close(Request $request, QvctActionPlan $plan): JsonResponse
    {
        $this->authorize('update', $plan);

        return response()->json($this->service->close($plan, $request->user()));
    }

    public function storeItem(StoreQvctActionPlanItemRequest $request, QvctActionPlan $plan): JsonResponse
    {
        $item = $this->service->addItem($plan, $request->validated());

        return response()->json($item, 201);
    }

    public function updateItemStatus(Request $request, QvctActionPlanItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $request->validate([
            'status' => ['required', 'string', Rule::in(array_column(QvctActionPlanItemStatus::cases(), 'value'))],
        ]);

        return response()->json($this->service->updateItemStatus($item, $request->input('status')));
    }

    public function recordImpact(Request $request, QvctActionPlanItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $request->validate([
            'impact_measurement_actual' => ['required', 'string', 'max:5000'],
        ]);

        return response()->json($this->service->recordImpact(
            $item,
            $request->input('impact_measurement_actual'),
        ));
    }
}
