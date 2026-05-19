<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QvctActionPlanItemStatus;
use App\Enums\QvctActionPlanStatus;
use App\Enums\QvctCampaignStatus;
use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctMood;
use App\Http\Requests\Qvct\StoreExchangeRequestRequest;
use App\Http\Requests\Qvct\StoreJournalEntryRequest;
use App\Models\QvctActionPlan;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctWeakSignal;
use App\Models\User;
use App\Services\ExchangeRequestService;
use App\Services\JournalEntryService;
use App\Services\QvctService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M3 — QVCT (Qualité de Vie et Conditions de Travail).
 *
 * Inertia hub — all reads query real tenant-scoped models.
 * Write paths are wired to the shared services used by the mobile API.
 */
class QvctController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $totalUsers = User::query()->where('structure_id', $user->structure_id)->count() ?: 1;

        $campaigns = QvctCampaign::query()
            ->with(['questionnaire:id,title'])
            ->withCount(['responses', 'weakSignals'])
            ->orderByDesc('opens_at')
            ->get();

        $campagnes = $campaigns->map(fn (QvctCampaign $c) => [
            'id' => $c->id,
            'titre' => $c->questionnaire?->title ?? '—',
            'date_debut' => $c->opens_at?->toDateString(),
            'date_fin' => $c->closes_at?->toDateString(),
            'statut' => match ($c->status) {
                QvctCampaignStatus::Active => 'active',
                QvctCampaignStatus::Closed,
                QvctCampaignStatus::Archived => 'terminee',
                QvctCampaignStatus::Draft => 'planifiee',
            },
            'statut_label' => $c->status->label(),
            'taux_participation' => (int) round($c->responses_count / $totalUsers * 100),
            'score_moyen' => null,
            'nb_reponses' => $c->responses_count,
            'nb_alertes' => $c->weak_signals_count,
        ]);

        $activeCampaign = $campaigns->first(fn (QvctCampaign $c) => $c->status === QvctCampaignStatus::Active);
        $lastClosed = $campaigns
            ->filter(fn (QvctCampaign $c) => $c->status === QvctCampaignStatus::Closed)
            ->sortByDesc('closes_at')
            ->first();

        $stats = [
            'score_moyen' => null,
            'taux_participation' => $activeCampaign
                ? (int) round($activeCampaign->responses_count / $totalUsers * 100)
                : null,
            'alertes_actives' => QvctWeakSignal::query()->whereNull('acknowledged_at')->count(),
            'derniere_campagne' => $lastClosed?->closes_at?->toDateString(),
        ];

        // Engagement trend: participation % per last 7 closed campaigns, ascending.
        $trend = $campaigns
            ->filter(fn (QvctCampaign $c) => $c->status === QvctCampaignStatus::Closed)
            ->sortByDesc('closes_at')
            ->take(7)
            ->values()
            ->map(fn (QvctCampaign $c) => [
                'label' => $c->closes_at?->format('M Y') ?? '—',
                'value' => round($c->responses_count / $totalUsers * 100, 1),
            ])
            ->reverse()
            ->values();

        // Top 5 outstanding weak signals for the hub summary card.
        $weakSignals = QvctWeakSignal::query()
            ->whereNull('acknowledged_at')
            ->orderByDesc('severity')
            ->limit(5)
            ->get()
            ->map(fn (QvctWeakSignal $s) => [
                'id' => $s->id,
                'type' => $s->signal_type->value,
                'team' => $s->team_tag,
                'score' => $s->severity,
                'detected_at' => $s->created_at?->diffForHumans() ?? '—',
                'acknowledged' => $s->isAcknowledged(),
            ]);

        return Inertia::render('dashboard/qvct/index', [
            'campagnes' => $campagnes,
            'stats' => $stats,
            'trend' => $trend,
            'weak_signals' => $weakSignals,
            'team_breakdown' => [],
        ]);
    }

    public function questionnaire(): Response
    {
        $campaign = QvctCampaign::query()
            ->with(['questionnaire:id,title,questions'])
            ->where('status', QvctCampaignStatus::Active->value)
            ->latest('opens_at')
            ->first();

        $questions = collect($campaign?->questionnaire?->questions ?? [])
            ->map(fn (array $q) => [
                'id' => $q['key'],
                'type' => 'likert',
                'label' => $q['label'],
                'min_label' => 'Pas du tout',
                'max_label' => 'Totalement',
                'required' => true,
            ])
            ->values();

        return Inertia::render('dashboard/qvct/questionnaire', [
            'campagne' => $campaign ? [
                'id' => $campaign->id,
                'titre' => $campaign->questionnaire?->title ?? '—',
                'date_fin' => $campaign->closes_at?->toDateString(),
                'description' => null,
            ] : null,
            'questions' => $questions,
            'threshold' => 5,
        ]);
    }

    public function store(Request $request, QvctService $service): RedirectResponse
    {
        $this->authorize('qvct.respond');

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required'],
            'team_tag' => ['nullable', 'string', 'max:64'],
        ]);

        $campaign = QvctCampaign::query()
            ->where('status', QvctCampaignStatus::Active->value)
            ->latest('opens_at')
            ->first();

        if ($campaign === null) {
            return back()->with('info', 'Aucune campagne active en ce moment.');
        }

        $service->recordResponse(
            $campaign,
            $validated['answers'],
            teamTag: $validated['team_tag'] ?? null,
        );

        return back()->with('success', 'Vos réponses ont été enregistrées anonymement. Merci !');
    }

    public function weakSignals(): Response
    {
        $signals = QvctWeakSignal::query()
            ->with(['campaign:id,title'])
            ->orderByDesc('severity')
            ->orderByDesc('created_at')
            ->get();

        $weekAgo = Carbon::now()->subWeek();

        $stats = [
            'total' => $signals->count(),
            'unacknowledged' => $signals->filter(fn ($s) => $s->acknowledged_at === null)->count(),
            'critical' => $signals->filter(fn ($s) => $s->severity >= 8)->count(),
            'this_week' => $signals->filter(fn ($s) => $s->created_at?->gte($weekAgo))->count(),
        ];

        $mapped = $signals->map(fn (QvctWeakSignal $s) => [
            'id' => $s->id,
            'type' => $s->signal_type->value,
            'team' => $s->team_tag,
            'score' => $s->severity,
            'detected_at' => $s->created_at?->diffForHumans() ?? '—',
            'severity' => $s->severity >= 8 ? 'critical' : 'attention',
            'respondents' => 0,
            'acknowledged' => $s->isAcknowledged(),
            'campaign' => $s->campaign?->title ?? '—',
        ]);

        return Inertia::render('dashboard/qvct/weak-signals/index', [
            'signals' => $mapped,
            'stats' => $stats,
        ]);
    }

    public function acknowledgeWeakSignal(string $id): RedirectResponse
    {
        $signal = QvctWeakSignal::findOrFail($id);
        $this->authorize('acknowledge', $signal);

        if (! $signal->isAcknowledged()) {
            $signal->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => request()->user()->id,
            ]);
        }

        return back()->with('success', 'Signal faible pris en compte.');
    }

    public function indicators(): Response
    {
        // Dimensions derived from the active questionnaire's question keys.
        $questionnaire = QvctQuestionnaire::query()
            ->where('is_active', true)
            ->latest()
            ->first();

        $dimensions = collect($questionnaire?->questions ?? [])
            ->map(fn (array $q) => [
                'key' => $q['key'],
                'label' => $q['label'],
                'description' => '',
            ])
            ->values();

        // Trend: response count per last 7 closed campaigns (engagement proxy).
        $trend = QvctCampaign::query()
            ->where('status', QvctCampaignStatus::Closed->value)
            ->withCount('responses')
            ->orderBy('closes_at')
            ->take(7)
            ->get()
            ->map(fn (QvctCampaign $c) => [
                'label' => $c->closes_at?->format('M Y') ?? '—',
                'value' => $c->responses_count,
            ]);

        // Team×dimension heatmap requires the PsychosocialRiskCartographyService
        // computing grouped response scores. Returning empty until that is wired.
        return Inertia::render('dashboard/qvct/indicators/index', [
            'dimensions' => $dimensions,
            'matrix' => ['teams' => [], 'cells' => []],
            'trend' => $trend,
        ]);
    }

    public function actionPlans(): Response
    {
        $plans = QvctActionPlan::query()
            ->with(['items', 'createdBy:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $plans->count(),
            'open' => $plans->filter(fn ($p) => in_array($p->status, [
                QvctActionPlanStatus::Draft,
                QvctActionPlanStatus::Published,
            ]))->count(),
            'closed' => $plans->filter(fn ($p) => $p->status === QvctActionPlanStatus::Closed)->count(),
            'items_in_progress' => $plans->sum(fn ($p) => $p->items
                ->filter(fn ($i) => $i->status === QvctActionPlanItemStatus::InProgress)
                ->count()
            ),
        ];

        $mapped = $plans->map(fn (QvctActionPlan $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'status' => $p->status->value,
            'status_label' => $p->status->label(),
            'published_at' => $p->published_at?->toDateString(),
            'closed_at' => $p->closed_at?->toDateString(),
            'owner' => $p->createdBy
                ? trim("{$p->createdBy->first_name} {$p->createdBy->last_name}")
                : '—',
            'items_total' => $p->items->count(),
            'items_done' => $p->items->filter(fn ($i) => $i->status === QvctActionPlanItemStatus::Done)->count(),
            'items_in_progress' => $p->items->filter(fn ($i) => $i->status === QvctActionPlanItemStatus::InProgress)->count(),
            'impact_target' => null,
            'tone' => match ($p->status) {
                QvctActionPlanStatus::Published => 'sage',
                QvctActionPlanStatus::Draft => 'warning',
                QvctActionPlanStatus::Closed => 'neutral',
            },
        ]);

        return Inertia::render('dashboard/qvct/action-plans/index', [
            'plans' => $mapped,
            'stats' => $stats,
        ]);
    }

    public function journal(JournalEntryService $service): Response
    {
        $user = request()->user();
        $entries = $service->listForUser($user);

        $mapped = $entries->map(fn ($e) => [
            'id' => $e->id,
            'date' => $e->created_at?->toDateString() ?? '—',
            'mood' => $e->mood_score ?? 3,
            'content' => $e->body,
            'shared_with_rh' => $e->shared_with_rh,
            'shared_at' => $e->shared_with_rh ? $e->updated_at?->toDateString() : null,
        ]);

        return Inertia::render('dashboard/qvct/journal/index', [
            'entries' => $mapped,
            'shared_count' => $entries->where('shared_with_rh', true)->count(),
        ]);
    }

    public function storeJournalEntry(StoreJournalEntryRequest $request, JournalEntryService $service): RedirectResponse
    {
        $service->write(
            $request->user(),
            $request->validated('body'),
            QvctMood::from($request->validated('mood')),
            (bool) $request->validated('shared_with_rh', false),
        );

        return back()->with('success', 'Entrée enregistrée.');
    }

    public function exchanges(ExchangeRequestService $service): Response
    {
        $user = request()->user();

        $inbox = $service->incomingFor($user)->map(fn ($e) => [
            'id' => $e->id,
            'from' => trim(($e->requester?->first_name ?? '').' '.($e->requester?->last_name ?? '')),
            'subject' => 'Demande d\'échange',
            'reason' => $e->message ?? '—',
            'status' => $e->status->value,
            'status_label' => $e->status->label(),
            'requested_at' => $e->created_at?->diffForHumans() ?? '—',
            'scheduled_at' => $e->scheduled_at?->isoFormat('dddd D MMM, HH[h]mm') ?? null,
        ]);

        $outbox = $service->outgoingFor($user)->map(fn ($e) => [
            'id' => $e->id,
            'to' => $e->addressee_role?->label() ?? '—',
            'subject' => 'Demande d\'échange',
            'status' => $e->status->value,
            'status_label' => $e->status->label(),
            'requested_at' => $e->created_at?->diffForHumans() ?? '—',
            'scheduled_at' => $e->scheduled_at?->isoFormat('dddd D MMM, HH[h]mm') ?? null,
        ]);

        return Inertia::render('dashboard/qvct/exchanges/index', [
            'inbox' => $inbox,
            'outbox' => $outbox,
        ]);
    }

    public function storeExchange(StoreExchangeRequestRequest $request, ExchangeRequestService $service): RedirectResponse
    {
        $service->create(
            $request->user(),
            QvctExchangeAddresseeRole::from($request->validated('addressee_role')),
            $request->validated('message'),
        );

        return back()->with('success', 'Demande d\'échange envoyée.');
    }
}
