<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\QvctActionPlanStatus;
use App\Enums\QvctCampaignStatus;
use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use App\Enums\QvctMood;
use App\Http\Requests\Qvct\StoreExchangeRequestRequest;
use App\Http\Requests\Qvct\StoreJournalEntryRequest;
use App\Models\QvctActionPlan;
use App\Models\QvctCampaign;
use App\Models\QvctExchangeRequest;
use App\Models\QvctJournalEntry;
use App\Models\QvctWeakSignal;
use App\Models\User;
use App\Services\ExchangeRequestService;
use App\Services\JournalEntryService;
use App\Services\PsychosocialRiskCartographyService;
use App\Services\QvctService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function __construct(
        private readonly JournalEntryService $journals,
        private readonly ExchangeRequestService $exchanges,
        private readonly PsychosocialRiskCartographyService $cartography,
    ) {}

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
            ->where('structure_id', currentStructure()?->id)
            ->where('status', QvctCampaignStatus::Active->value)
            ->with(['questionnaire:id,title,questions'])
            ->orderByDesc('opens_at')
            ->first();

        $questions = collect($campaign?->questionnaire?->questions ?? [])
            ->map(fn (array $q) => [
                'id' => (string) ($q['key'] ?? ''),
                'type' => match ($q['scale'] ?? '1-5') {
                    'yes_no' => 'open',
                    default => 'likert',
                },
                'label' => (string) ($q['label'] ?? ''),
                'help' => null,
                'min_label' => 'Pas d\'accord',
                'max_label' => 'Tout à fait d\'accord',
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

        $weekAgo = now()->subWeek();

        $stats = [
            'total' => $signals->count(),
            'unacknowledged' => $signals->filter(fn ($s) => $s->acknowledged_at === null)->count(),
            'critical' => $signals->filter(fn ($s) => $s->severity >= 8)->count(),
            'this_week' => $signals->filter(fn ($s) => $s->created_at?->gte($weekAgo))->count(),
        ];

        $mapped = $signals->map(fn (QvctWeakSignal $s) => [
            'id' => $s->id,
            'type' => $s->signal_type instanceof \BackedEnum ? $s->signal_type->value : (string) $s->signal_type,
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
        $campaign = QvctCampaign::query()
            ->where('structure_id', currentStructure()?->id)
            ->whereNotNull('questionnaire_id')
            ->orderByDesc('opens_at')
            ->first();

        $rows = $campaign
            ? $this->cartography->cartographyFor($campaign->load('questionnaire'))
            : collect();

        $teams = $rows->map(fn (array $r): string => $r['team_tag'] ?? 'Toute la structure')->values()->all();

        $cells = [];
        foreach ($rows as $row) {
            $teamLabel = $row['team_tag'] ?? 'Toute la structure';
            foreach ($row['mean_scores'] as $dimensionKey => $meanScore) {
                $cells[] = [
                    'team' => $teamLabel,
                    'dimension' => (string) $dimensionKey,
                    'score' => (float) $meanScore,
                ];
            }
        }

        $dimensions = collect($rows->flatMap(fn (array $r): array => array_keys($r['mean_scores'])))
            ->unique()
            ->map(fn (string $k): array => [
                'key' => $k,
                'label' => ucfirst(str_replace('_', ' ', $k)),
                'description' => '',
            ])
            ->values()
            ->all();

        $hasReal = ! empty($cells);

        return Inertia::render('dashboard/qvct/indicators/index', [
            'dimensions' => $hasReal ? $dimensions : [],
            'matrix' => $hasReal ? ['teams' => $teams, 'cells' => $cells] : ['teams' => [], 'cells' => []],
            'trend' => [],
        ]);
    }

    public function actionPlans(): Response
    {
        $plans = QvctActionPlan::query()
            ->with(['createdBy:id,first_name,last_name'])
            ->withCount([
                'items as items_total',
                'items as items_done' => fn ($q) => $q->where('status', 'done'),
                'items as items_in_progress' => fn ($q) => $q->where('status', 'in_progress'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $plans->count(),
            'open' => $plans->filter(fn ($p) => in_array($p->status, [
                QvctActionPlanStatus::Draft,
                QvctActionPlanStatus::Published,
            ]))->count(),
            'closed' => $plans->filter(fn ($p) => $p->status === QvctActionPlanStatus::Closed)->count(),
            'items_in_progress' => $plans->sum('items_in_progress'),
        ];

        $mapped = $plans->map(fn (QvctActionPlan $p): array => [
            'id' => $p->id,
            'title' => $p->title,
            'status' => $p->status->value,
            'status_label' => $p->status->label(),
            'published_at' => $p->published_at?->toIso8601String(),
            'closed_at' => $p->closed_at?->toIso8601String(),
            'owner' => $p->createdBy
                ? trim($p->createdBy->first_name.' '.$p->createdBy->last_name)
                : '—',
            'items_total' => (int) $p->items_total,
            'items_done' => (int) $p->items_done,
            'items_in_progress' => (int) $p->items_in_progress,
            'impact_target' => (string) ($p->target_quarter ?? ''),
            'tone' => match ($p->status) {
                QvctActionPlanStatus::Published => 'sage',
                QvctActionPlanStatus::Draft => 'warning',
                QvctActionPlanStatus::Closed => 'brand',
            },
        ]);

        return Inertia::render('dashboard/qvct/action-plans/index', [
            'plans' => $mapped,
            'stats' => $stats,
        ]);
    }

    public function journal(Request $request): Response
    {
        $entries = $this->journals->listForUser($request->user())
            ->map(fn (QvctJournalEntry $e): array => [
                'id' => $e->id,
                'date' => $e->created_at?->format('Y-m-d H:i') ?? '',
                'mood' => (int) $e->mood_score,
                'content' => (string) $e->body,
                'shared_with_rh' => (bool) $e->shared_with_rh,
                'shared_at' => $e->shared_with_rh && $e->updated_at
                    ? $e->updated_at->format('Y-m-d H:i')
                    : null,
            ])
            ->all();

        $sharedCount = QvctJournalEntry::query()
            ->where('user_id', $request->user()->id)
            ->where('shared_with_rh', true)
            ->count();

        return Inertia::render('dashboard/qvct/journal/index', [
            'entries' => $entries,
            'shared_count' => $sharedCount,
        ]);
    }

    public function storeJournalEntry(StoreJournalEntryRequest $request): RedirectResponse
    {
        $this->journals->write(
            $request->user(),
            $request->validated('body'),
            QvctMood::from($request->validated('mood')),
            (bool) $request->validated('shared_with_rh', false),
        );

        return back()->with('success', 'Entrée enregistrée.');
    }

    public function exchanges(Request $request): Response
    {
        $me = $request->user();

        $inbox = $this->exchanges->incomingFor($me)
            ->map(fn (QvctExchangeRequest $e): array => [
                'id' => $e->id,
                'from' => $e->requester
                    ? trim($e->requester->first_name.' '.$e->requester->last_name)
                    : '—',
                'subject' => 'Demande d\'échange',
                'reason' => mb_substr((string) $e->message, 0, 80),
                'status' => $e->status instanceof \BackedEnum ? $e->status->value : (string) $e->status,
                'status_label' => $e->status instanceof QvctExchangeStatus
                    ? $e->status->label()
                    : (string) $e->status,
                'requested_at' => $e->created_at?->format('Y-m-d H:i') ?? '',
                'scheduled_at' => $e->scheduled_at?->format('Y-m-d H:i'),
            ])
            ->all();

        $outbox = $this->exchanges->outgoingFor($me)
            ->map(fn (QvctExchangeRequest $e): array => [
                'id' => $e->id,
                'to' => $e->addressee_role instanceof QvctExchangeAddresseeRole
                    ? $e->addressee_role->label()
                    : (string) $e->addressee_role,
                'subject' => 'Demande d\'échange',
                'status' => $e->status instanceof \BackedEnum ? $e->status->value : (string) $e->status,
                'status_label' => $e->status instanceof QvctExchangeStatus
                    ? $e->status->label()
                    : (string) $e->status,
                'requested_at' => $e->created_at?->format('Y-m-d H:i') ?? '',
                'scheduled_at' => $e->scheduled_at?->format('Y-m-d H:i'),
            ])
            ->all();

        return Inertia::render('dashboard/qvct/exchanges/index', [
            'inbox' => $inbox,
            'outbox' => $outbox,
        ]);
    }

    public function storeExchange(StoreExchangeRequestRequest $request): RedirectResponse
    {
        $this->exchanges->create(
            $request->user(),
            QvctExchangeAddresseeRole::from($request->validated('addressee_role')),
            $request->validated('message'),
        );

        return back()->with('success', 'Demande d\'échange envoyée.');
    }
}
