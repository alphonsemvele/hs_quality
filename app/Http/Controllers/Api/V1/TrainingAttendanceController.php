<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competencies\MarkAttendedRequest;
use App\Http\Requests\Competencies\RegisterAttendanceRequest;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\TrainingPlanService;
use Illuminate\Http\JsonResponse;

class TrainingAttendanceController extends Controller
{
    public function __construct(private readonly TrainingPlanService $service) {}

    public function register(RegisterAttendanceRequest $request, TrainingSession $session): JsonResponse
    {
        $this->authorize('view', $session);
        $this->authorize('create', TrainingAttendance::class);

        // Self-service path: when no user_id is in the payload, register
        // the authenticated user. RH/coord can register someone else by
        // supplying user_id.
        $target = $request->validated('user_id')
            ? User::query()->findOrFail($request->validated('user_id'))
            : $request->user();

        $attendance = $this->service->register($session, $target, recorder: $request->user());

        return response()->json($attendance, 201);
    }

    public function markAttended(MarkAttendedRequest $request, TrainingAttendance $attendance): JsonResponse
    {
        $this->authorize('update', $attendance);

        return response()->json(
            $this->service->markAttended($attendance, $request->validated('notes')),
        );
    }

    public function cancel(TrainingAttendance $attendance): JsonResponse
    {
        // Self-cancel allowed (own attendance) OR trainings.record.
        $user = request()->user();

        if ($attendance->user_id !== $user->id && ! $user->hasPermissionTo('trainings.record')) {
            abort(403);
        }

        return response()->json($this->service->cancel($attendance));
    }
}
