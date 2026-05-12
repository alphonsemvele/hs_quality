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

    public function create(): Response
    {
        $this->authorize('create', QvctCampaign::class);

        $questionnaires = QvctQuestionnaire::query()
            ->where('is_active', true)
            ->withCount(['campaigns'])
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'frequency', 'questions', 'created_at']);

        return Inertia::render('dashboard/qvct/campaigns/create', [
            'questionnaires' => $questionnaires->map(fn (QvctQuestionnaire $q) => [
                'id' => $q->id,
                'title' => $q->title,
                'frequency' => $q->frequency instanceof \BackedEnum ? $q->frequency->value : $q->frequency,
                'question_count' => is_array($q->questions) ? count($q->questions) : 0,
                'campaigns_count' => $q->campaigns_count ?? 0,
            ])->all(),
            'teams' => $this->demoTeams(),
        ]);
    }

    /**
     * @return list<array{value: string, label: string, members: int}>
     */
    private function demoTeams(): array
    {
        return [
            ['value' => '', 'label' => 'Toute la structure', 'members' => 0],
            ['value' => 'secteur_nord', 'label' => 'Secteur Nord', 'members' => 6],
            ['value' => 'secteur_sud', 'label' => 'Secteur Sud', 'members' => 5],
            ['value' => 'secteur_est', 'label' => 'Secteur Est', 'members' => 4],
            ['value' => 'secteur_ouest', 'label' => 'Secteur Ouest', 'members' => 4],
            ['value' => 'coordination', 'label' => 'Coordination', 'members' => 3],
        ];
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
