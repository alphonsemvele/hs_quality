<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TrainingPlanStatus;
use App\Http\Requests\Competencies\AddSessionRequest;
use App\Http\Requests\Competencies\DraftTrainingPlanRequest;
use App\Http\Requests\Competencies\MarkAttendedRequest;
use App\Http\Requests\Competencies\RecordCertificationRequest;
use App\Http\Requests\Competencies\RecordHabilitationRequest;
use App\Http\Requests\Competencies\RegisterAttendanceRequest;
use App\Http\Requests\Competencies\UpdateTrainingPlanRequest;
use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\HabilitationService;
use App\Services\TrainingPlanService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation.
 *
 * Inertia hub aggregating plans, sessions, habilitations and certifications.
 * Dedicated API controllers (`Api\V1\TrainingPlanController`,
 * `TrainingAttendanceController`, `HabilitationController`,
 * `CertificationController`) keep the write paths for mobile parity. The
 * web store/update share the same TrainingPlanService as the API.
 */
class FormationController extends Controller
{
    public function __construct(
        private readonly TrainingPlanService $service,
        private readonly HabilitationService $habilitations,
    ) {}

    public function index(): Response
    {
        $today = Carbon::today();
        $soon = Carbon::today()->addDays(30);

        $certifications = Certification::query()
            ->with(['user:id,first_name,last_name'])
            ->orderBy('expires_at')
            ->get()
            ->map(fn (Certification $c) => $this->mapCertification($c, $today, $soon));

        $habilitations = Habilitation::query()
            ->with(['user:id,first_name,last_name'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Habilitation $h) => $this->mapHabilitation($h, $today, $soon));

        $formations = $certifications->concat($habilitations)->sortBy('date_expiration')->values();

        $stats = [
            'total' => $formations->count(),
            'a_jour' => $formations->filter(fn ($f) => $f['statut'] === 'valide')->count(),
            'expirant_bientot' => $formations->filter(fn ($f) => $f['statut'] === 'expire_bientot')->count(),
            'expirees' => $formations->filter(fn ($f) => $f['statut'] === 'expiree')->count(),
        ];

        $plans = TrainingPlan::query()
            ->withCount('sessions')
            ->with(['sessions' => fn ($q) => $q->withCount('attendances')])
            ->orderByDesc('year')
            ->get()
            ->map(fn (TrainingPlan $p) => [
                'id' => $p->id,
                'year' => $p->year,
                'theme' => $p->theme,
                'target_audience' => $p->target_audience ?? '',
                'status' => $p->status->value,
                'status_label' => $this->planStatusLabel($p->status),
                'sessions_total' => $p->sessions_count,
                'sessions_done' => 0,
                'participants_total' => $p->sessions->sum('attendances_count'),
                'participants_done' => 0,
            ]);

        $sessions = TrainingSession::query()
            ->with(['plan:id,theme'])
            ->withCount('attendances')
            ->where('starts_at', '>=', $today)
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(fn (TrainingSession $s) => [
                'id' => $s->id,
                'date' => $s->starts_at?->toDateString() ?? '—',
                'time' => $s->starts_at?->format('H:i') ?? '—',
                'title' => $s->title,
                'location' => $s->location ?? '—',
                'capacity' => $s->capacity,
                'registered' => $s->attendances_count,
                'status' => $s->attendances_count >= $s->capacity ? 'full' : 'scheduled',
            ]);

        return Inertia::render('dashboard/formations/index', [
            'formations' => $formations,
            'stats' => $stats,
            'plans' => $plans,
            'sessions' => $sessions,
            'expiringAlerts' => $this->buildExpiringAlerts(),
        ]);
    }

    public function createPlan(): Response
    {
        $this->authorize('create', TrainingPlan::class);

        return Inertia::render('dashboard/formations/plans/create');
    }

    public function store(DraftTrainingPlanRequest $request): RedirectResponse
    {
        $this->service->draft(
            structure: currentStructure(),
            author: $request->user(),
            year: (int) $request->validated('year'),
            theme: $request->validated('theme'),
            targetAudience: $request->validated('target_audience'),
        );

        return redirect()->route('formations.index')
            ->with('success', 'Plan de formation créé.');
    }

    public function editPlan(TrainingPlan $plan): Response
    {
        $this->authorize('update', $plan);

        return Inertia::render('dashboard/formations/plans/edit', [
            'plan' => [
                'id' => $plan->id,
                'year' => $plan->year,
                'theme' => $plan->theme,
                'target_audience' => $plan->target_audience ?? '',
                'status' => $plan->status->value,
                'status_label' => $this->planStatusLabel($plan->status),
            ],
        ]);
    }

    public function update(UpdateTrainingPlanRequest $request, TrainingPlan $plan): RedirectResponse
    {
        $this->service->update($plan, $request->validated());

        return redirect()->route('formations.index')
            ->with('success', 'Plan de formation mis à jour.');
    }

    public function createSession(TrainingPlan $plan): Response
    {
        $this->authorize('update', $plan);

        return Inertia::render('dashboard/formations/sessions/create', [
            'plan' => [
                'id' => $plan->id,
                'year' => $plan->year,
                'theme' => $plan->theme,
            ],
        ]);
    }

    public function storeSession(AddSessionRequest $request, TrainingPlan $plan): RedirectResponse
    {
        $trainerId = $request->validated('trainer_user_id');

        $session = $this->service->addSession(
            $plan,
            title: $request->validated('title'),
            startsAt: Carbon::parse($request->validated('starts_at')),
            endsAt: Carbon::parse($request->validated('ends_at')),
            capacity: (int) $request->validated('capacity'),
            trainerName: $request->validated('trainer_name'),
            trainer: $trainerId ? User::query()->find($trainerId) : null,
            location: $request->validated('location'),
        );

        return redirect()->route('formations.sessions.show', $session)
            ->with('success', 'Session ajoutée au plan.');
    }

    public function showSession(Request $request, TrainingSession $session): Response
    {
        $this->authorize('view', $session->plan);

        $session->load([
            'plan:id,year,theme',
            'trainer:id,first_name,last_name',
            'attendances.user:id,first_name,last_name',
        ]);

        return Inertia::render('dashboard/formations/sessions/show', [
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'plan_title' => $session->plan?->theme ?? '',
                'plan_id' => $session->training_plan_id,
                'date' => $session->starts_at?->toDateString(),
                'time' => $session->starts_at?->format('H:i'),
                'duration_minutes' => $session->starts_at && $session->ends_at
                    ? $session->starts_at->diffInMinutes($session->ends_at)
                    : null,
                'location' => $session->location,
                'capacity' => (int) $session->capacity,
                'registered' => $session->attendances
                    ->filter(fn ($a): bool => ($a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status) === 'registered')
                    ->count(),
                'attended' => $session->attendances
                    ->filter(fn ($a): bool => ($a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status) === 'attended')
                    ->count(),
                'status' => 'scheduled',
                'organisme' => $session->trainer_name ?? ($session->trainer
                    ? trim(($session->trainer->first_name ?? '').' '.($session->trainer->last_name ?? ''))
                    : ''),
                'description' => '',
            ],
            'eligible_users' => User::query()
                ->where('structure_id', $session->structure_id)
                ->whereNull('deleted_at')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(200)
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $u): array => [
                    'id' => (int) $u->id,
                    'full_name' => trim(($u->first_name ?? '').' '.($u->last_name ?? '')),
                ])
                ->all(),
            'attendees' => $session->attendances->map(fn ($a): array => [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'name' => $a->user
                    ? trim(($a->user->first_name ?? '').' '.($a->user->last_name ?? ''))
                    : '—',
                'initials' => $a->user
                    ? mb_strtoupper(mb_substr($a->user->first_name ?? '?', 0, 1).mb_substr($a->user->last_name ?? '', 0, 1))
                    : '?',
                'role' => 'Intervenant',
                'status' => $a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status,
                'status_label' => match ($a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status) {
                    'registered' => 'Inscrit',
                    'attended' => 'Présent',
                    'cancelled' => 'Annulée',
                    default => (string) $a->status,
                },
                'last_psc1' => null,
            ])->all(),
        ]);
    }

    public function registerAttendance(RegisterAttendanceRequest $request, TrainingSession $session): RedirectResponse
    {
        $this->authorize('view', $session);
        $this->authorize('create', TrainingAttendance::class);

        $target = $request->validated('user_id')
            ? User::query()->findOrFail($request->validated('user_id'))
            : $request->user();

        $this->service->register($session, $target, recorder: $request->user());

        return back()->with('success', 'Inscription enregistrée.');
    }

    public function markAttended(MarkAttendedRequest $request, TrainingAttendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        $this->service->markAttended($attendance, $request->validated('notes'));

        return back()->with('success', 'Présence marquée.');
    }

    public function cancelAttendance(Request $request, TrainingAttendance $attendance): RedirectResponse
    {
        $user = $request->user();
        if ($attendance->user_id !== $user->id && ! $user->hasPermissionTo('trainings.record')) {
            abort(403);
        }

        $this->service->cancel($attendance);

        return back()->with('success', 'Inscription annulée.');
    }

    public function myCompetencies(Request $request): Response
    {
        $me = $request->user();

        $habilitations = Habilitation::query()
            ->where('user_id', $me->id)
            ->orderByDesc('valid_until')
            ->get()
            ->map(fn (Habilitation $h): array => $this->mapCompetency(
                $h->id,
                $h->type,
                $h->reference_number,
                $h->valid_from?->toDateString(),
                $h->valid_until?->toDateString(),
            ))
            ->all();

        $certifications = Certification::query()
            ->where('user_id', $me->id)
            ->orderByDesc('expires_at')
            ->get()
            ->map(fn (Certification $c): array => $this->mapCompetency(
                $c->id,
                $c->type,
                $c->reference_number,
                $c->issued_on?->toDateString(),
                $c->expires_at?->toDateString(),
            ))
            ->all();

        $enrollments = TrainingAttendance::query()
            ->where('user_id', $me->id)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>=', now()))
            ->with('session:id,title,location,starts_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn (TrainingAttendance $a): array => [
                'id' => (string) $a->id,
                'session_id' => $a->session?->id ?? '',
                'title' => $a->session?->title ?? '',
                'date' => $a->session?->starts_at?->toDateString() ?? '',
                'location' => $a->session?->location ?? '',
                'status' => $a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status,
                'status_label' => match ($a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status) {
                    'registered' => 'Inscrit',
                    'attended' => 'Présent',
                    'cancelled' => 'Annulée',
                    default => (string) $a->status,
                },
            ])
            ->all();

        return Inertia::render('dashboard/formations/competencies/mine', [
            'me' => [
                'name' => $me->fullName(),
                'role' => $me->type instanceof \BackedEnum ? $me->type->value : (string) $me->type,
            ],
            'habilitations' => $habilitations,
            'certifications' => $certifications,
            'enrollments' => $enrollments,
        ]);
    }

    public function storeHabilitation(RecordHabilitationRequest $request): RedirectResponse
    {
        $target = User::query()->findOrFail($request->validated('user_id'));

        $this->habilitations->record(
            user: $target,
            type: $request->validated('type'),
            referenceNumber: $request->validated('reference_number'),
            validFrom: $request->validated('valid_from') ? Carbon::parse($request->validated('valid_from')) : null,
            validUntil: $request->validated('valid_until') ? Carbon::parse($request->validated('valid_until')) : null,
            evidencePath: $request->validated('evidence_path'),
            recorder: $request->user(),
        );

        return back()->with('success', 'Habilitation enregistrée.');
    }

    public function storeCertification(RecordCertificationRequest $request): RedirectResponse
    {
        $target = User::query()->findOrFail($request->validated('user_id'));

        Certification::create([
            'structure_id' => $target->structure_id,
            'user_id' => $target->id,
            'type' => $request->validated('type'),
            'reference_number' => $request->validated('reference_number'),
            'issued_on' => $request->validated('issued_on'),
            'expires_at' => $request->validated('expires_at'),
            'evidence_path' => $request->validated('evidence_path'),
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Certification enregistrée.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildExpiringAlerts(): array
    {
        $today = now()->startOfDay();

        $habilitationAlerts = Habilitation::query()
            ->with('user:id,first_name,last_name')
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', $today->copy()->addDays(180))
            ->orderBy('valid_until')
            ->limit(20)
            ->get()
            ->map(fn (Habilitation $h): array => $this->mapExpiringAlert(
                'h-'.$h->id,
                $h->user,
                $h->type,
                $h->valid_until?->toDateString(),
                $today,
            ));

        $certificationAlerts = Certification::query()
            ->with('user:id,first_name,last_name')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $today->copy()->addDays(180))
            ->orderBy('expires_at')
            ->limit(20)
            ->get()
            ->map(fn (Certification $c): array => $this->mapExpiringAlert(
                'c-'.$c->id,
                $c->user,
                $c->type,
                $c->expires_at?->toDateString(),
                $today,
            ));

        return collect()->concat($habilitationAlerts)->concat($certificationAlerts)
            ->sortBy('date_expiration')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapExpiringAlert(string $id, ?User $user, string $intitule, ?string $expiration, CarbonInterface $today): array
    {
        $days = $expiration !== null
            ? (int) Carbon::parse($expiration)->startOfDay()->diffInDays($today, false) * -1
            : 0;
        $severity = $days < 0 ? 'expired' : ($days <= 60 ? 'urgent' : 'warning');

        return [
            'id' => $id,
            'intervenant' => $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '—',
            'intitule' => $intitule,
            'date_expiration' => (string) $expiration,
            'days_to_expiry' => $days,
            'severity' => $severity,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCompetency(string $id, string $type, ?string $referenceNumber, ?string $obtained, ?string $expiry): array
    {
        $daysToExpiry = $expiry !== null
            ? (int) Carbon::parse($expiry)->startOfDay()->diffInDays(now()->startOfDay(), false) * -1
            : 0;

        $statut = $expiry === null
            ? 'valide'
            : ($daysToExpiry < 0 ? 'expiree' : ($daysToExpiry <= 60 ? 'expire_bientot' : 'valide'));

        return [
            'id' => $id,
            'intitule' => $type,
            'organisme' => $referenceNumber,
            'date_obtention' => $obtained,
            'date_expiration' => $expiry,
            'days_to_expiry' => $daysToExpiry,
            'statut' => $statut,
        ];
    }

    private function planStatusLabel(TrainingPlanStatus $status): string
    {
        return match ($status) {
            TrainingPlanStatus::Draft => 'Brouillon',
            TrainingPlanStatus::Published => 'Publié',
            TrainingPlanStatus::Archived => 'Archivé',
        };
    }

    /** @return array<string, mixed> */
    private function mapCertification(Certification $c, Carbon $today, Carbon $soon): array
    {
        return [
            'id' => $c->id,
            'intervenant' => trim(($c->user?->first_name ?? '').' '.($c->user?->last_name ?? '')),
            'initials' => mb_strtoupper(
                mb_substr($c->user?->first_name ?? '?', 0, 1).
                mb_substr($c->user?->last_name ?? '?', 0, 1)
            ),
            'intitule' => $c->type,
            'organisme' => null,
            'date_obtention' => $c->issued_on?->toDateString(),
            'date_expiration' => $c->expires_at?->toDateString(),
            'days_to_expiry' => $c->daysUntilExpiry($today),
            'statut' => match (true) {
                $c->expires_at < $today => 'expiree',
                $c->expires_at <= $soon => 'expire_bientot',
                default => 'valide',
            },
            'statut_label' => match (true) {
                $c->expires_at < $today => 'Expirée',
                $c->expires_at <= $soon => 'Expire bientôt',
                default => 'Valide',
            },
            'type' => 'certification',
        ];
    }

    /** @return array<string, mixed> */
    private function mapHabilitation(Habilitation $h, Carbon $today, Carbon $soon): array
    {
        $expireSoon = $h->valid_until !== null && $h->valid_until <= $soon;
        $expired = $h->valid_until !== null && $h->valid_until < $today;

        return [
            'id' => $h->id,
            'intervenant' => trim(($h->user?->first_name ?? '').' '.($h->user?->last_name ?? '')),
            'initials' => mb_strtoupper(
                mb_substr($h->user?->first_name ?? '?', 0, 1).
                mb_substr($h->user?->last_name ?? '?', 0, 1)
            ),
            'intitule' => $h->type,
            'organisme' => null,
            'date_obtention' => $h->valid_from?->toDateString(),
            'date_expiration' => $h->valid_until?->toDateString(),
            'days_to_expiry' => $h->valid_until
                ? (int) $today->startOfDay()->diffInDays($h->valid_until, absolute: false)
                : 9999,
            'statut' => match (true) {
                $expired => 'expiree',
                $expireSoon => 'expire_bientot',
                default => 'valide',
            },
            'statut_label' => match (true) {
                $expired => 'Expirée',
                $expireSoon => 'Expire bientôt',
                default => 'Valide',
            },
            'type' => 'habilitation',
        ];
    }
}
