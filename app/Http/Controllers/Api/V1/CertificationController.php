<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competencies\RecordCertificationRequest;
use App\Models\Certification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CertificationController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Certification::class);

        $user = request()->user();

        $query = Certification::query()->with('user:id,first_name,last_name');

        if (! $user->hasAnyPermission(['certifications.view.team', 'certifications.view.structure'])) {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'data' => $query->orderBy('expires_at')->limit(200)->get(),
        ]);
    }

    public function store(RecordCertificationRequest $request): JsonResponse
    {
        $this->authorize('create', Certification::class);

        // The Certification has a wider write surface than Habilitation —
        // expires_at is required, last_alert_window is set by the job.
        $certification = Certification::create([
            'user_id' => $request->validated('user_id'),
            'type' => $request->validated('type'),
            'reference_number' => $request->validated('reference_number'),
            'issued_on' => Carbon::parse($request->validated('issued_on')),
            'expires_at' => Carbon::parse($request->validated('expires_at')),
            'evidence_path' => $request->validated('evidence_path'),
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json($certification, 201);
    }

    public function show(Certification $certification): JsonResponse
    {
        $this->authorize('view', $certification);

        return response()->json($certification->load('user:id,first_name,last_name'));
    }

    public function destroy(Certification $certification): JsonResponse
    {
        $this->authorize('delete', $certification);

        $certification->delete();

        return response()->json(null, 204);
    }
}
