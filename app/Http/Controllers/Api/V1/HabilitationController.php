<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competencies\RecordHabilitationRequest;
use App\Models\Habilitation;
use App\Models\User;
use App\Services\HabilitationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class HabilitationController extends Controller
{
    public function __construct(private readonly HabilitationService $habilitations) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Habilitation::class);

        $user = request()->user();

        // Intervenants only see their own; everyone else sees the structure-wide list.
        $query = Habilitation::query()->with('user:id,first_name,last_name');

        if (! $user->hasAnyPermission(['certifications.view.team', 'certifications.view.structure'])) {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'data' => $query->orderByDesc('created_at')->limit(200)->get(),
        ]);
    }

    public function store(RecordHabilitationRequest $request): JsonResponse
    {
        $target = User::query()->findOrFail($request->validated('user_id'));

        $habilitation = $this->habilitations->record(
            $target,
            type: $request->validated('type'),
            referenceNumber: $request->validated('reference_number'),
            validFrom: $request->validated('valid_from') ? Carbon::parse($request->validated('valid_from')) : null,
            validUntil: $request->validated('valid_until') ? Carbon::parse($request->validated('valid_until')) : null,
            evidencePath: $request->validated('evidence_path'),
            recorder: $request->user(),
        );

        return response()->json($habilitation, 201);
    }

    public function show(Habilitation $habilitation): JsonResponse
    {
        $this->authorize('view', $habilitation);

        return response()->json($habilitation->load('user:id,first_name,last_name'));
    }

    public function destroy(Habilitation $habilitation): JsonResponse
    {
        $this->authorize('delete', $habilitation);

        $this->habilitations->expire($habilitation);

        return response()->json(null, 204);
    }
}
