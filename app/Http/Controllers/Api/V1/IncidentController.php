<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incidents\StoreIncidentRequest;
use App\Http\Resources\Api\V1\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Incident::class);

        return IncidentResource::collection(
            Incident::query()
                ->orderByDesc('occurred_at')
                ->paginate(50),
        );
    }

    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $incident = $this->service->declare($request->validated(), $request->user());

        return response()->json(new IncidentResource($incident), 201);
    }

    public function show(Incident $incident): JsonResponse
    {
        $this->authorize('view', $incident);

        return response()->json(new IncidentResource($incident));
    }
}
