<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competencies\AddSessionRequest;
use App\Http\Requests\Competencies\DraftTrainingPlanRequest;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\TrainingPlanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TrainingPlanController extends Controller
{
    public function __construct(private readonly TrainingPlanService $service) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', TrainingPlan::class);

        return response()->json([
            'data' => TrainingPlan::query()
                ->withCount('sessions')
                ->orderByDesc('year')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function store(DraftTrainingPlanRequest $request): JsonResponse
    {
        $plan = $this->service->draft(
            structure: currentStructure(),
            author: $request->user(),
            year: (int) $request->validated('year'),
            theme: $request->validated('theme'),
            targetAudience: $request->validated('target_audience'),
        );

        return response()->json($plan, 201);
    }

    public function show(TrainingPlan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        return response()->json($plan->load('sessions'));
    }

    public function publish(TrainingPlan $plan): JsonResponse
    {
        $this->authorize('publish', $plan);

        return response()->json($this->service->publish($plan));
    }

    public function archive(TrainingPlan $plan): JsonResponse
    {
        $this->authorize('archive', $plan);

        return response()->json($this->service->archive($plan));
    }

    public function addSession(AddSessionRequest $request, TrainingPlan $plan): JsonResponse
    {
        $this->authorize('create', TrainingSession::class);

        $trainerUserId = $request->validated('trainer_user_id');

        $session = $this->service->addSession(
            $plan,
            title: $request->validated('title'),
            startsAt: Carbon::parse($request->validated('starts_at')),
            endsAt: Carbon::parse($request->validated('ends_at')),
            capacity: (int) $request->validated('capacity'),
            trainerName: $request->validated('trainer_name'),
            trainer: $trainerUserId ? User::query()->find($trainerUserId) : null,
            location: $request->validated('location'),
        );

        return response()->json($session, 201);
    }
}
