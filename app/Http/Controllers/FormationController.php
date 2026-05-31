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
        $demo = config('app.env') === 'local';

        $realPlans = TrainingPlan::query()
            ->withCount('sessions')
            ->orderByDesc('year')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TrainingPlan $p): array => [
                'id' => $p->id,
                'year' => $p->year,
                'theme' => $p->theme,
                'target_audience' => $p->target_audience ?? '',
                'status' => $p->status->value,
                'status_label' => $this->planStatusLabel($p->status),
                'sessions_total' => (int) $p->sessions_count,
                'sessions_done' => 0,
                'participants_total' => 0,
                'participants_done' => 0,
            ])
            ->all();

        // Fall back to demo plans only when there is no real data yet — once
        // the structure has created its first plan, the demo content steps
        // aside so the UI reflects the real state.
        $plans = (! empty($realPlans) || ! $demo) ? $realPlans : $this->demoTrainingPlans();

        $realSessions = TrainingSession::query()
            ->with('attendances:id,training_session_id,status')
            ->orderBy('starts_at')
            ->limit(50)
            ->get()
            ->map(function (TrainingSession $s): array {
                $registered = $s->attendances
                    ->filter(fn ($a): bool => ($a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status) === 'registered')
                    ->count();

                return [
                    'id' => $s->id,
                    'date' => $s->starts_at?->toDateString() ?? '',
                    'time' => $s->starts_at?->format('H:i') ?? '',
                    'title' => $s->title,
                    'location' => $s->location ?? '',
                    'capacity' => (int) $s->capacity,
                    'registered' => $registered,
                    'status' => $registered >= (int) $s->capacity ? 'full' : 'scheduled',
                ];
            })
            ->all();

        $sessions = (! empty($realSessions) || ! $demo) ? $realSessions : $this->demoSessions();

        return Inertia::render('dashboard/formations/index', [
            'formations' => $demo ? $this->demoFormations() : [],
            'stats' => $demo
                ? ['total' => 8, 'a_jour' => 5, 'expirant_bientot' => 2, 'expirees' => 1]
                : ['total' => 0, 'a_jour' => 0, 'expirant_bientot' => 0, 'expirees' => 0],
            'plans' => $plans,
            'sessions' => $sessions,
            'expiringAlerts' => $this->buildExpiringAlerts() ?: ($demo ? $this->demoExpiringAlerts() : []),
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

    private function planStatusLabel(TrainingPlanStatus $status): string
    {
        return match ($status) {
            TrainingPlanStatus::Draft => 'Brouillon',
            TrainingPlanStatus::Published => 'Publié',
            TrainingPlanStatus::Archived => 'Archivé',
        };
    }

    /**
     * Demo session detail with attendance roster. Backend session model
     * (TrainingSession + TrainingAttendance) exists but the Inertia wiring
     * here serves demo data until the dedicated controller writes are
     * exposed to the web layer.
     */
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
        // Self-cancel allowed; otherwise needs trainings.record.
        if ($attendance->user_id !== $user->id && ! $user->hasPermissionTo('trainings.record')) {
            abort(403);
        }

        $this->service->cancel($attendance);

        return back()->with('success', 'Inscription annulée.');
    }

    /**
     * "Mes compétences" — vue intervenant centrée sur ses habilitations
     * et certifications personnelles. Persona-aware: filters by the
     * current user's identity.
     */
    public function myCompetencies(Request $request): Response
    {
        $demo = config('app.env') === 'local';
        $me = $request->user();

        $realHabilitations = Habilitation::query()
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

        $realCertifications = Certification::query()
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

        $realEnrollments = TrainingAttendance::query()
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

        $hasReal = ! empty($realHabilitations) || ! empty($realCertifications) || ! empty($realEnrollments);

        return Inertia::render('dashboard/formations/competencies/mine', [
            'me' => [
                'name' => $me->fullName(),
                'role' => $me->type instanceof \BackedEnum ? $me->type->value : (string) $me->type,
            ],
            'habilitations' => $hasReal || ! $demo ? $realHabilitations : $this->demoMyHabilitations(),
            'certifications' => $hasReal || ! $demo ? $realCertifications : $this->demoMyCertifications(),
            'enrollments' => $hasReal || ! $demo ? $realEnrollments : $this->demoMyEnrollments(),
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
     * Build a unified expirations calendar from real habilitations +
     * certifications (joined to their user). Severity reflects how urgent
     * the renewal is: < 0 → expirée, ≤ 60 → urgent, ≤ 180 → warning.
     *
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
     * Compute the shared (id,intitule,organisme,dates,status) shape both
     * habilitations and certifications expose to the competencies page.
     *
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

    /**
     * @return array<string, mixed>
     */
    private function demoSessionDetail(string $id): array
    {
        return [
            'id' => $id,
            'title' => 'PSC1 — Session de rappel',
            'plan_title' => 'Renouvellement PSC1 — Année 2026',
            'date' => '2026-05-14',
            'time' => '09:00',
            'duration_minutes' => 240,
            'location' => 'Croix-Rouge Paris 11e',
            'capacity' => 8,
            'registered' => 6,
            'attended' => 0,
            'status' => 'scheduled',
            'organisme' => 'Croix-Rouge française',
            'description' => 'Rappel des gestes de premiers secours — réanimation, position latérale de sécurité, malaise cardiaque, étouffement. Examen blanc en fin de session.',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoAttendees(): array
    {
        return [
            ['id' => 'att-1', 'user_id' => 'u-1', 'name' => 'Marie Leclerc', 'initials' => 'ML', 'role' => 'Intervenante', 'status' => 'registered', 'status_label' => 'Inscrite', 'last_psc1' => '2024-06-10'],
            ['id' => 'att-2', 'user_id' => 'u-2', 'name' => 'Luc Moreau', 'initials' => 'LM', 'role' => 'Intervenant', 'status' => 'registered', 'status_label' => 'Inscrit', 'last_psc1' => '2024-06-10'],
            ['id' => 'att-3', 'user_id' => 'u-3', 'name' => 'Sophie Bernard', 'initials' => 'SB', 'role' => 'Intervenante', 'status' => 'registered', 'status_label' => 'Inscrite', 'last_psc1' => null],
            ['id' => 'att-4', 'user_id' => 'u-4', 'name' => 'Karim Benali', 'initials' => 'KB', 'role' => 'Intervenant', 'status' => 'registered', 'status_label' => 'Inscrit', 'last_psc1' => '2023-09-15'],
            ['id' => 'att-5', 'user_id' => 'u-5', 'name' => 'Claire Bernard', 'initials' => 'CB', 'role' => 'Référente qualité', 'status' => 'cancelled', 'status_label' => 'Annulée', 'last_psc1' => '2024-06-10'],
            ['id' => 'att-6', 'user_id' => 'u-6', 'name' => 'Thomas Dupont', 'initials' => 'TD', 'role' => 'Coordinateur', 'status' => 'registered', 'status_label' => 'Inscrit', 'last_psc1' => '2024-06-10'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoMyHabilitations(): array
    {
        return [
            ['id' => 'h-1', 'intitule' => 'Aide à la toilette et soins d\'hygiène', 'organisme' => 'INRS', 'date_obtention' => '2025-03-15', 'date_expiration' => '2027-03-15', 'days_to_expiry' => 670, 'statut' => 'valide'],
            ['id' => 'h-2', 'intitule' => 'Aide à la prise médicamenteuse', 'organisme' => 'ARS IDF', 'date_obtention' => '2025-02-01', 'date_expiration' => '2026-08-01', 'days_to_expiry' => 82, 'statut' => 'expire_bientot'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoMyCertifications(): array
    {
        return [
            ['id' => 'c-1', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2024-06-10', 'date_expiration' => '2026-06-10', 'days_to_expiry' => 30, 'statut' => 'expire_bientot'],
            ['id' => 'c-2', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2024-11-20', 'date_expiration' => '2026-11-20', 'days_to_expiry' => 193, 'statut' => 'valide'],
            ['id' => 'c-3', 'intitule' => 'Accompagnement Alzheimer', 'organisme' => 'France Alzheimer', 'date_obtention' => '2025-09-01', 'date_expiration' => '2027-09-01', 'days_to_expiry' => 843, 'statut' => 'valide'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoMyEnrollments(): array
    {
        return [
            ['id' => 'e-1', 'session_id' => 's-001', 'title' => 'PSC1 — Session de rappel', 'date' => '2026-05-14', 'location' => 'Croix-Rouge Paris 11e', 'status' => 'registered', 'status_label' => 'Inscrit'],
            ['id' => 'e-2', 'session_id' => 's-002', 'title' => 'Bientraitance — Module 1', 'date' => '2026-05-22', 'location' => 'Visioconférence', 'status' => 'registered', 'status_label' => 'Inscrit'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoFormations(): array
    {
        return [
            ['id' => 'f1', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Aide à la toilette et soins d\'hygiène', 'organisme' => 'INRS', 'date_obtention' => '2025-03-15', 'date_expiration' => '2027-03-15', 'days_to_expiry' => 670, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Habilitation'],
            ['id' => 'f2', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2024-11-20', 'date_expiration' => '2026-11-20', 'days_to_expiry' => 193, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f3', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2024-06-10', 'date_expiration' => '2026-06-10', 'days_to_expiry' => 30, 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Certification'],
            ['id' => 'f4', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Accompagnement Alzheimer', 'organisme' => 'France Alzheimer', 'date_obtention' => '2025-09-01', 'date_expiration' => '2027-09-01', 'days_to_expiry' => 843, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f5', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2023-09-15', 'date_expiration' => '2025-09-15', 'days_to_expiry' => -238, 'statut' => 'expiree', 'statut_label' => 'Expirée', 'type' => 'Certification'],
            ['id' => 'f6', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2025-01-10', 'date_expiration' => '2027-01-10', 'days_to_expiry' => 609, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f7', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Bientraitance et prévention maltraitance', 'organisme' => 'ANESM', 'date_obtention' => '2025-06-20', 'date_expiration' => '2028-06-20', 'days_to_expiry' => 1135, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f8', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Aide à la prise médicamenteuse', 'organisme' => 'ARS IDF', 'date_obtention' => '2025-02-01', 'date_expiration' => '2026-08-01', 'days_to_expiry' => 82, 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Habilitation'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoTrainingPlans(): array
    {
        return [
            [
                'id' => 'tp-001',
                'year' => 2026,
                'theme' => 'Bientraitance et prévention RPS',
                'target_audience' => 'Tous intervenants',
                'status' => 'published',
                'status_label' => 'Publié',
                'sessions_total' => 4,
                'sessions_done' => 2,
                'participants_total' => 24,
                'participants_done' => 14,
            ],
            [
                'id' => 'tp-002',
                'year' => 2026,
                'theme' => 'Renouvellement PSC1',
                'target_audience' => 'Intervenants dont certif < 6 mois',
                'status' => 'published',
                'status_label' => 'Publié',
                'sessions_total' => 3,
                'sessions_done' => 1,
                'participants_total' => 12,
                'participants_done' => 4,
            ],
            [
                'id' => 'tp-003',
                'year' => 2026,
                'theme' => 'Accompagnement fin de vie',
                'target_audience' => 'Volontaires + référent qualité',
                'status' => 'draft',
                'status_label' => 'Brouillon',
                'sessions_total' => 0,
                'sessions_done' => 0,
                'participants_total' => 0,
                'participants_done' => 0,
            ],
            [
                'id' => 'tp-004',
                'year' => 2025,
                'theme' => 'Hygiène et soins de base',
                'target_audience' => 'Tous intervenants',
                'status' => 'archived',
                'status_label' => 'Archivé',
                'sessions_total' => 4,
                'sessions_done' => 4,
                'participants_total' => 22,
                'participants_done' => 22,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoSessions(): array
    {
        return [
            ['id' => 's-001', 'date' => '2026-05-14', 'time' => '09:00', 'title' => 'PSC1 — Session de rappel', 'location' => 'Croix-Rouge Paris 11e', 'capacity' => 8, 'registered' => 6, 'status' => 'scheduled'],
            ['id' => 's-002', 'date' => '2026-05-22', 'time' => '14:00', 'title' => 'Bientraitance — Module 1', 'location' => 'Visioconférence', 'capacity' => 15, 'registered' => 12, 'status' => 'scheduled'],
            ['id' => 's-003', 'date' => '2026-05-28', 'time' => '10:00', 'title' => 'Manutention — Atelier pratique', 'location' => 'INRS', 'capacity' => 6, 'registered' => 6, 'status' => 'full'],
            ['id' => 's-004', 'date' => '2026-06-04', 'time' => '09:00', 'title' => 'Bientraitance — Module 2', 'location' => 'Visioconférence', 'capacity' => 15, 'registered' => 8, 'status' => 'scheduled'],
            ['id' => 's-005', 'date' => '2026-06-18', 'time' => '14:00', 'title' => 'PSC1 — Session de rappel', 'location' => 'Croix-Rouge Paris 11e', 'capacity' => 8, 'registered' => 3, 'status' => 'scheduled'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoExpiringAlerts(): array
    {
        return [
            ['id' => 'f3', 'intervenant' => 'Luc Moreau', 'intitule' => 'PSC1 — Premiers secours', 'date_expiration' => '2026-06-10', 'days_to_expiry' => 30, 'severity' => 'urgent'],
            ['id' => 'f8', 'intervenant' => 'Luc Moreau', 'intitule' => 'Aide à la prise médicamenteuse', 'date_expiration' => '2026-08-01', 'days_to_expiry' => 82, 'severity' => 'warning'],
            ['id' => 'f5', 'intervenant' => 'Marie Leclerc', 'intitule' => 'PSC1 — Premiers secours', 'date_expiration' => '2025-09-15', 'days_to_expiry' => -238, 'severity' => 'expired'],
        ];
    }
}
