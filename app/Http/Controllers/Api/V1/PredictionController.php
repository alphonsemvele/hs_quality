<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PredictionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePredictionRequest;
use App\Jobs\DispatchPredictionJob;
use App\Models\PredictionRequest;
use App\Services\MLPredictionService;
use Illuminate\Http\JsonResponse;

class PredictionController extends Controller
{
    public function __construct(private readonly MLPredictionService $service) {}

    /**
     * Return the latest prediction per type for the authenticated structure.
     *
     * Frontend reads this to populate dashboard cards and "en calcul"
     * placeholders. Non-existent types return null so the frontend knows
     * no prediction has been requested yet.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PredictionRequest::class);

        $structure = currentStructure();

        $latest = collect(PredictionType::cases())
            ->mapWithKeys(fn (PredictionType $type): array => [
                $type->value => $this->service->latestFor($structure, $type),
            ]);

        return response()->json(['data' => $latest]);
    }

    /**
     * Trigger a new prediction request.
     *
     * Creates the PredictionRequest record synchronously (so the client
     * gets a 201 with the record id immediately), then queues
     * DispatchPredictionJob to submit it to the ML service asynchronously.
     */
    public function store(StorePredictionRequest $request): JsonResponse
    {
        $type = PredictionType::from($request->validated('type'));
        $inputData = $request->validated('input_data');
        $structure = currentStructure();

        $prediction = $this->service->request($structure, $request->user(), $type, $inputData);

        DispatchPredictionJob::dispatch($prediction);

        return response()->json($prediction, 201);
    }
}
