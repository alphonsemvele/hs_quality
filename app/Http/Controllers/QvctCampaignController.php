<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Qvct\LaunchQvctCampaignRequest;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Services\QvctService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QvctCampaignController extends Controller
{
    public function __construct(private readonly QvctService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', QvctCampaign::class);

        return Inertia::render('dashboard/qvct/campaigns/index', [
            'campaigns' => QvctCampaign::query()
                ->with(['questionnaire:id,title,frequency'])
                ->withCount(['responses', 'weakSignals'])
                ->orderByDesc('opens_at')
                ->paginate(20),
        ]);
    }

    public function show(QvctCampaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'questionnaire:id,title,frequency,questions',
            'launchedBy:id,first_name,last_name',
            'weakSignals',
        ]);

        return Inertia::render('dashboard/qvct/campaigns/show', [
            'campaign' => $campaign,
            'response_count' => $campaign->responses()->count(),
        ]);
    }

    public function launch(LaunchQvctCampaignRequest $request, QvctQuestionnaire $questionnaire): RedirectResponse
    {
        $campaign = $this->service->launchCampaign(
            $questionnaire,
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('qvct.campaigns.show', $campaign)
            ->with('success', 'Campagne lancée.');
    }

    public function close(QvctCampaign $campaign): RedirectResponse
    {
        $this->authorize('close', $campaign);

        $this->service->closeCampaign($campaign);

        return back()->with('success', 'Campagne clôturée — détection des signaux faibles déclenchée.');
    }
}
