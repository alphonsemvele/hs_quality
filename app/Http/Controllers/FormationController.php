<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation.
 *
 * Inertia hub aggregating plans, sessions, habilitations and certifications.
 * Dedicated API controllers (Api\V1\TrainingPlanController, etc.) handle
 * mobile write paths — this controller only renders the web surfaces.
 */
class FormationController extends Controller
{
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
                'status_label' => $p->status->label(),
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
            'expiringAlerts' => $certifications->filter(fn ($f) => $f['statut'] === 'expire_bientot')->values(),
        ]);
    }

    public function showSession(Request $request, string $id): Response
    {
        $session = TrainingSession::with([
            'plan:id,theme,year',
            'attendances.user:id,first_name,last_name',
        ])->findOrFail($id);

        $attendees = $session->attendances->map(fn (TrainingAttendance $a) => [
            'id' => $a->id,
            'name' => trim(($a->user?->first_name ?? '').' '.($a->user?->last_name ?? '')),
            'initials' => mb_strtoupper(
                mb_substr($a->user?->first_name ?? '?', 0, 1).
                mb_substr($a->user?->last_name ?? '?', 0, 1)
            ),
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'notes' => $a->notes,
        ]);

        return Inertia::render('dashboard/formations/sessions/show', [
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'date' => $session->starts_at?->toDateString(),
                'time' => $session->starts_at?->format('H:i'),
                'location' => $session->location,
                'capacity' => $session->capacity,
                'plan' => $session->plan?->theme,
            ],
            'attendees' => $attendees,
        ]);
    }

    public function myCompetencies(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();
        $soon = $today->copy()->addDays(30);

        $habilitations = Habilitation::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Habilitation $h) => [
                'id' => $h->id,
                'type' => $h->type,
                'valid_from' => $h->valid_from?->toDateString(),
                'valid_until' => $h->valid_until?->toDateString(),
            ]);

        $certifications = Certification::query()
            ->where('user_id', $user->id)
            ->orderBy('expires_at')
            ->get()
            ->map(fn (Certification $c) => [
                'id' => $c->id,
                'type' => $c->type,
                'issued_on' => $c->issued_on?->toDateString(),
                'expires_at' => $c->expires_at?->toDateString(),
                'days_to_expiry' => $c->daysUntilExpiry($today),
                'statut' => match (true) {
                    $c->expires_at < $today => 'expiree',
                    $c->expires_at <= $soon => 'expire_bientot',
                    default => 'valide',
                },
            ]);

        $enrollments = TrainingAttendance::query()
            ->where('user_id', $user->id)
            ->with(['session:id,title,starts_at,location'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TrainingAttendance $a) => [
                'id' => $a->id,
                'session' => $a->session?->title ?? '—',
                'date' => $a->session?->starts_at?->toDateString(),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
            ]);

        return Inertia::render('dashboard/formations/competencies/mine', [
            'habilitations' => $habilitations,
            'certifications' => $certifications,
            'enrollments' => $enrollments,
        ]);
    }

    public function store(): RedirectResponse
    {
        return back()->with('info', 'Création de plan : utilisez le module Formations → Nouveau plan.');
    }

    public function update(string $id): RedirectResponse
    {
        return back()->with('info', 'Mise à jour : utilisez le module Formations.');
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
