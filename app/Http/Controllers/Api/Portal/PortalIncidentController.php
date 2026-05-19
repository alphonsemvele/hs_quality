<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReportPortalIncidentRequest;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;

class PortalIncidentController extends Controller
{
    public function __construct(private readonly IncidentService $incidentService) {}

    /**
     * Allow a beneficiary to report an incident concerning their own care.
     *
     * Delegates to the shared IncidentService so the same classification
     * and notification logic (NotifyARSJob for grave/critique) fires
     * regardless of whether the report came from mobile, web, or portal.
     */
    public function store(ReportPortalIncidentRequest $request): JsonResponse
    {
        $user = $request->user();
        $beneficiary = $user->beneficiary;

        if ($beneficiary === null) {
            abort(422, 'Aucun bénéficiaire lié à ce compte portail.');
        }

        $incident = $this->incidentService->declare(
            array_merge($request->validated(), ['beneficiary_id' => $beneficiary->id]),
            $user,
        );

        return response()->json($incident, 201);
    }
}
