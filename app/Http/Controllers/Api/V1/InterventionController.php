<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interventions\CancelInterventionRequest;
use App\Http\Requests\Interventions\CheckOutInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionPhotoRequest;
use App\Http\Requests\Interventions\StoreInterventionSignatureRequest;
use App\Http\Requests\Interventions\SubmitInterventionReportRequest;
use App\Http\Resources\Api\V1\InterventionResource;
use App\Models\Intervention;
use App\Models\InterventionPhoto;
use App\Services\InterventionMediaService;
use App\Services\InterventionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InterventionController extends Controller
{
    public function __construct(
        private readonly InterventionService $service,
        private readonly InterventionMediaService $mediaService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Intervention::class);

        $user = $request->user();

        $query = Intervention::query()
            ->with('beneficiary:id,first_name,last_name')
            ->orderBy('planned_date')
            ->orderBy('planned_start_time');

        // Intervenants only see their own interventions.
        if ($user->hasPermissionTo('interventions.view.own') && ! $user->hasPermissionTo('interventions.view.structure')) {
            $query->where('intervenant_id', $user->id);
        }

        // Optional date filter — mobile clients fetch per-day.
        if ($request->filled('date')) {
            $query->whereDate('planned_date', $request->input('date'));
        }

        return InterventionResource::collection($query->paginate(50));
    }

    public function show(Intervention $intervention): JsonResponse
    {
        $this->authorize('view', $intervention);

        $intervention->load('beneficiary');

        return response()->json(new InterventionResource($intervention));
    }

    public function checkIn(Request $request, Intervention $intervention): JsonResponse
    {
        $this->authorize('checkIn', $intervention);

        $updated = $this->service->checkIn(
            $intervention,
            $request->only('latitude', 'longitude'),
        );

        return response()->json(new InterventionResource($updated));
    }

    public function checkOut(CheckOutInterventionRequest $request, Intervention $intervention): JsonResponse
    {
        $updated = $this->service->checkOut($intervention, $request->validated());

        return response()->json(new InterventionResource($updated));
    }

    public function cancel(CancelInterventionRequest $request, Intervention $intervention): JsonResponse
    {
        $updated = $this->service->cancel($intervention, $request->validated('cancellation_reason'));

        return response()->json(new InterventionResource($updated));
    }

    public function submitReport(SubmitInterventionReportRequest $request, Intervention $intervention): JsonResponse
    {
        $updated = $this->service->submitReport(
            $intervention,
            $request->validated('report_text'),
            $request->user(),
        );

        return response()->json(new InterventionResource($updated));
    }

    public function storePhoto(StoreInterventionPhotoRequest $request, Intervention $intervention): JsonResponse
    {
        $photo = $this->mediaService->storePhoto($intervention, $request->file('photo'), $request->user());

        return response()->json(['id' => $photo->id, 'url' => $this->mediaService->signedUrl($photo)], 201);
    }

    public function destroyPhoto(Request $request, Intervention $intervention, InterventionPhoto $photo): JsonResponse
    {
        $this->authorize('update', $intervention);

        abort_if($photo->intervention_id !== $intervention->id, 404);

        $this->mediaService->deletePhoto($photo);

        return response()->json(null, 204);
    }

    public function storeSignature(StoreInterventionSignatureRequest $request, Intervention $intervention): JsonResponse
    {
        $signature = $this->mediaService->storeSignature(
            $intervention,
            $request->validated('signature'),
            $request->validated('signer_type'),
            $request->user(),
        );

        return response()->json(['id' => $signature->id, 'url' => $this->mediaService->signedUrl($signature)], 201);
    }
}
