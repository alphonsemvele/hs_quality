<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QvctCampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qvct\SubmitQvctResponseRequest;
use App\Models\QvctCampaign;
use App\Services\QvctService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QvctCampaignController extends Controller
{
    public function __construct(private readonly QvctService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctCampaign::class);

        // Mobile clients fetch only currently-open campaigns by default —
        // intervenants only need to know what's accepting responses today.
        // Web clients can pass ?status=all for the full list.
        $query = QvctCampaign::query()
            ->select(['id', 'title', 'opens_at', 'closes_at', 'status', 'questionnaire_id'])
            ->with(['questionnaire:id,title,questions'])
            ->orderByDesc('opens_at');

        if ($request->input('status', 'open') === 'open') {
            $query->where('status', QvctCampaignStatus::Active->value);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function show(QvctCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign->load(['questionnaire:id,title,questions,frequency']);

        return response()->json($campaign);
    }

    public function submitResponse(SubmitQvctResponseRequest $request, QvctCampaign $campaign): JsonResponse
    {
        // Anonymity contract: never echo the response id back to the client.
        $response = $this->service->recordResponse(
            $campaign,
            $request->validated('answers'),
            teamTag: $request->validated('team_tag'),
        );

        return response()->json([
            'campaign_id' => $campaign->id,
            'submitted_at' => $response->submitted_at?->toIso8601String(),
        ], 201);
    }
}
