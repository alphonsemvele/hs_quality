<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Http\Requests\Beneficiaries\StoreBeneficiaryRequest;
use App\Http\Requests\Beneficiaries\StoreSatisfactionRatingRequest;
use App\Http\Requests\Beneficiaries\UpdateBeneficiaryContactsRequest;
use App\Http\Requests\Beneficiaries\UpdateBeneficiaryRequest;
use App\Http\Resources\BeneficiaryDossierResource;
use App\Http\Resources\BeneficiaryResource;
use App\Http\Resources\IntervenantAssignmentResource;
use App\Models\Beneficiary;
use App\Models\BeneficiarySatisfactionRating;
use App\Models\CarePlan;
use App\Models\Incident;
use App\Models\IntervenantAssignment;
use App\Models\Intervention;
use App\Models\User;
use App\Services\BeneficiaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia admin controller for Beneficiaries.
 *
 * Thin orchestration layer — every method delegates business logic to
 * BeneficiaryService and authorizes through BeneficiaryPolicy. Same
 * pattern will be repeated in the future Api\V1\BeneficiaryController
 * for the React Native mobile app, both calling the same service.
 *
 * Routes: see routes/web.php (/beneficiaries/*).
 * Sensitive dossier endpoint is guarded by log_sensitive_read middleware
 * so every health-data access is audit-logged per CDC §5.2.
 */
class BeneficiaryController extends Controller
{
    public function __construct(
        private readonly BeneficiaryService $service,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Beneficiary::class);

        // Wave 1 / H2 — assigned-vs-structure scope lives in the service
        // so this same logic applies on the mobile API. Don't reintroduce
        // an inline scope here; keep both surfaces in sync via the service.
        $beneficiaries = $this->service
            ->listForUser(request()->user())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return Inertia::render('dashboard/beneficiaries/index', [
            'beneficiaries' => BeneficiaryResource::collection($beneficiaries),
            'meta' => [
                'total' => $beneficiaries->total(),
                'current_page' => $beneficiaries->currentPage(),
                'last_page' => $beneficiaries->lastPage(),
            ],
        ]);
    }

    public function show(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);

        $assignments = IntervenantAssignment::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->with(['intervenant', 'assignedBy'])
            ->orderByDesc('assigned_at')
            ->get();

        // Eligible = intervenants in this tenant who are not currently
        // actively assigned. Surfacing only the unassigned pool keeps the
        // attach UI focused; the unique-active constraint at the DB also
        // catches duplicates if the page is stale.
        $activeIntervenantIds = $assignments
            ->where('unassigned_at', null)
            ->pluck('user_id')
            ->all();

        $canAssign = request()->user()->can('update', $beneficiary);

        $eligibleIntervenants = $canAssign
            ? User::query()
                ->where('type', UserType::Intervenant->value)
                ->whereNotIn('id', $activeIntervenantIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'full_name' => $u->fullName(),
                    'email' => $u->email,
                ])
                ->values()
            : collect();

        return Inertia::render('dashboard/beneficiaries/show', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
            'assignments' => IntervenantAssignmentResource::collection($assignments),
            'eligible_intervenants' => $eligibleIntervenants,
            'can_assign' => $canAssign,
        ]);
    }

    /**
     * Chronological journey for a single beneficiary. Aggregates
     * interventions, incidents, care-plan transitions and intervenant
     * assignments into one merged timeline, sorted newest-first.
     *
     * Each domain is read through its own model — global tenant scope
     * applies, so foreign rows are invisible (404, never 403). The view
     * authorization mirrors `show()` because the timeline reveals the same
     * set of attributes as the standard fiche.
     */
    public function timeline(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);

        $statutMap = [
            'planned' => 'planifiee',
            'in_progress' => 'en_cours',
            'completed' => 'realisee',
            'cancelled' => 'annulee',
            'missed' => 'non_realisee',
        ];

        // Date range — defaults to the last 12 months. `all=1` lifts the
        // filter for the rare full-history view. Hard safety caps stay in
        // place per source so a 10-year dossier with thousands of rows
        // still returns a bounded payload.
        $allowedRanges = [3, 6, 12, 24];
        $requestedMonths = (int) request()->query('months', 12);
        $months = in_array($requestedMonths, $allowedRanges, true) ? $requestedMonths : 12;
        $unbounded = request()->boolean('all');
        $since = $unbounded ? null : now()->subMonths($months)->startOfDay();

        $interventions = Intervention::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->when($since, fn ($q) => $q->where('planned_date', '>=', $since))
            ->with('intervenant:id,first_name,last_name')
            ->orderByDesc('planned_date')
            ->limit(300)
            ->get();

        $incidents = Incident::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->when($since, fn ($q) => $q->where('occurred_at', '>=', $since))
            ->orderByDesc('occurred_at')
            ->limit(150)
            ->get();

        $carePlans = CarePlan::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->when($since, fn ($q) => $q->where('start_date', '>=', $since->toDateString()))
            ->orderByDesc('start_date')
            ->limit(60)
            ->get();

        $assignments = IntervenantAssignment::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->when($since, fn ($q) => $q->where('assigned_at', '>=', $since))
            ->with('intervenant:id,first_name,last_name')
            ->orderByDesc('assigned_at')
            ->limit(150)
            ->get();

        $events = collect();

        foreach ($interventions as $intervention) {
            $intervenant = $intervention->intervenant
                ? trim($intervention->intervenant->first_name.' '.$intervention->intervenant->last_name)
                : 'Intervenant inconnu';

            $statusValue = $intervention->status?->value ?? 'planned';
            $statutFr = $statutMap[$statusValue] ?? 'planifiee';

            $occurredAt = $intervention->planned_date
                ? Carbon::parse(
                    $intervention->planned_date->toDateString().
                    ' '.($intervention->planned_start_time ?? '00:00:00'),
                )->toIso8601String()
                : Carbon::parse($intervention->created_at)->toIso8601String();

            $events->push([
                'id' => 'intervention-'.$intervention->id,
                'kind' => 'intervention',
                'occurred_at' => $occurredAt,
                'title' => 'Visite '.($intervention->status?->label() ?? 'planifiée'),
                'description' => 'Intervenant : '.$intervenant,
                'status' => $statutFr,
                'link' => route('interventions.show', $intervention),
            ]);
        }

        foreach ($incidents as $incident) {
            $events->push([
                'id' => 'incident-'.$incident->id,
                'kind' => 'incident',
                'occurred_at' => Carbon::parse($incident->occurred_at)->toIso8601String(),
                'title' => 'Incident · '.($incident->categorie?->value ?? 'non catégorisé'),
                'description' => $this->truncateText((string) ($incident->description ?? ''), 160),
                'status' => $incident->statut?->value ?? 'declare',
                'gravite' => $incident->gravite?->value ?? 'mineur',
                'link' => route('incidents.show', $incident),
            ]);
        }

        foreach ($carePlans as $plan) {
            $statusValue = $plan->status?->value ?? 'draft';
            $events->push([
                'id' => 'care-plan-'.$plan->id,
                'kind' => 'care_plan',
                'occurred_at' => Carbon::parse($plan->start_date ?? $plan->created_at)->toIso8601String(),
                'title' => 'Plan de soins · '.($plan->title ?? 'sans titre'),
                'description' => 'Statut : '.$statusValue,
                'status' => $statusValue,
                'link' => route('care-plans.show', $plan),
            ]);
        }

        foreach ($assignments as $assignment) {
            $intervenant = $assignment->intervenant
                ? trim($assignment->intervenant->first_name.' '.$assignment->intervenant->last_name)
                : 'Intervenant inconnu';

            $events->push([
                'id' => 'assignment-on-'.$assignment->id,
                'kind' => 'assignment_on',
                'occurred_at' => Carbon::parse($assignment->assigned_at)->toIso8601String(),
                'title' => 'Affectation · '.$intervenant,
                'description' => (string) ($assignment->notes ?? 'Aucune note'),
                'status' => 'active',
                'link' => route('beneficiaries.show', $beneficiary),
            ]);

            if ($assignment->unassigned_at !== null) {
                $events->push([
                    'id' => 'assignment-off-'.$assignment->id,
                    'kind' => 'assignment_off',
                    'occurred_at' => Carbon::parse($assignment->unassigned_at)->toIso8601String(),
                    'title' => 'Fin d\'affectation · '.$intervenant,
                    'description' => 'Désaffectation enregistrée.',
                    'status' => 'closed',
                    'link' => route('beneficiaries.show', $beneficiary),
                ]);
            }
        }

        $sorted = $events
            ->sortByDesc('occurred_at')
            ->values()
            ->all();

        return Inertia::render('dashboard/beneficiaries/timeline', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
            'events' => $sorted,
            'totals' => [
                'interventions' => $interventions->count(),
                'incidents' => $incidents->count(),
                'care_plans' => $carePlans->count(),
                'assignments' => $assignments->count(),
            ],
            'range' => [
                'months' => $months,
                'unbounded' => $unbounded,
                'since' => $since?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Non-medical contacts surface. Deliberately separated from the
     * dossier médical: this view never reads encrypted health fields
     * (allergies, medical_history, current_treatments, medical_notes).
     *
     * RH and coordinators with "beneficiaries.update" but without medical
     * read access can use this page to keep family/doctor/emergency
     * details up to date without ever touching health data.
     */
    public function contacts(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);

        return Inertia::render('dashboard/beneficiaries/contacts', [
            'beneficiary' => [
                'id' => $beneficiary->id,
                'full_name' => $beneficiary->fullName(),
                'initials' => mb_substr($beneficiary->first_name, 0, 1).mb_substr($beneficiary->last_name, 0, 1),
                'phone' => $beneficiary->phone,
                'email' => $beneficiary->email,
                'address' => $beneficiary->address,
                'postal_code' => $beneficiary->postal_code,
                'city' => $beneficiary->city,
                'primary_doctor' => $beneficiary->primary_doctor,
                'primary_doctor_phone' => $beneficiary->primary_doctor_phone,
                'emergency_contact_name' => $beneficiary->emergency_contact_name,
                'emergency_contact_phone' => $beneficiary->emergency_contact_phone,
                'emergency_contact_relationship' => $beneficiary->emergency_contact_relationship,
            ],
            'can_update' => request()->user()->can('update', $beneficiary),
        ]);
    }

    public function updateContacts(UpdateBeneficiaryContactsRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $beneficiary->fill($request->validated())->save();

        return redirect()
            ->route('beneficiaries.contacts', $beneficiary)
            ->with('success', 'Contacts mis à jour.');
    }

    /**
     * Satisfaction ratings — read view + inline form. Free text is encrypted
     * at the model layer so the audit trail records the score change but
     * never the comment ciphertext.
     */
    public function satisfaction(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);

        $ratings = BeneficiarySatisfactionRating::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->with('ratedBy:id,first_name,last_name')
            ->orderByDesc('rated_at')
            ->limit(60)
            ->get();

        $serialised = $ratings->map(fn (BeneficiarySatisfactionRating $r): array => [
            'id' => $r->id,
            'score' => $r->score,
            'comment' => $r->comment,
            'rated_at' => Carbon::parse($r->rated_at)->toDateString(),
            'rated_by' => $r->ratedBy
                ? trim($r->ratedBy->first_name.' '.$r->ratedBy->last_name)
                : 'Anonyme',
            'created_at' => $r->created_at?->toIso8601String(),
        ])->all();

        $average = $ratings->avg('score');

        return Inertia::render('dashboard/beneficiaries/satisfaction', [
            'beneficiary' => [
                'id' => $beneficiary->id,
                'full_name' => $beneficiary->fullName(),
                'initials' => mb_substr($beneficiary->first_name, 0, 1).mb_substr($beneficiary->last_name, 0, 1),
            ],
            'ratings' => $serialised,
            'stats' => [
                'count' => $ratings->count(),
                'average' => $average !== null ? round((float) $average, 1) : null,
                'last_rated_at' => $ratings->first()?->rated_at?->toDateString(),
            ],
            'can_record' => request()->user()->can('create', BeneficiarySatisfactionRating::class),
        ]);
    }

    public function storeSatisfaction(StoreSatisfactionRatingRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        BeneficiarySatisfactionRating::create([
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
            'intervention_id' => $request->validated('intervention_id'),
            'score' => $request->validated('score'),
            'comment' => $request->validated('comment'),
            'rated_at' => $request->validated('rated_at'),
            'rated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('beneficiaries.satisfaction', $beneficiary)
            ->with('success', 'Évaluation enregistrée.');
    }

    /**
     * Sensitive dossier — full medical file with health-data fields.
     * Guarded at the route level by log_sensitive_read middleware so
     * every access generates an audit entry.
     */
    public function dossier(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);

        return Inertia::render('dashboard/beneficiaries/dossier', [
            'beneficiary' => BeneficiaryDossierResource::make($beneficiary),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Beneficiary::class);

        return Inertia::render('dashboard/beneficiaries/create');
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        $beneficiary = $this->service->create($request->validated());

        return redirect()
            ->route('beneficiaries.show', $beneficiary)
            ->with('success', 'Bénéficiaire créé avec succès.');
    }

    public function edit(Beneficiary $beneficiary): Response
    {
        $this->authorize('update', $beneficiary);

        return Inertia::render('dashboard/beneficiaries/edit', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $this->service->update($beneficiary, $request->validated());

        return redirect()
            ->route('beneficiaries.show', $beneficiary)
            ->with('success', 'Bénéficiaire mis à jour.');
    }

    public function destroy(Beneficiary $beneficiary): RedirectResponse
    {
        $this->authorize('delete', $beneficiary);

        $beneficiary->delete();

        return redirect()
            ->route('beneficiaries.index')
            ->with('success', 'Bénéficiaire archivé.');
    }

    private function truncateText(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 1).'…';
    }
}
