<?php

namespace App\Http\Controllers;

use App\Enums\InterventionStatus;
use App\Enums\UserType;
use App\Enums\VisitMode;
use App\Http\Requests\Interventions\CancelInterventionRequest;
use App\Http\Requests\Interventions\CheckOutInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionPhotoRequest;
use App\Http\Requests\Interventions\StoreInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionSignatureRequest;
use App\Http\Requests\Interventions\UpdateInterventionRequest;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Intervention;
use App\Models\InterventionPhoto;
use App\Models\User;
use App\Services\InterventionMediaService;
use App\Services\InterventionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterventionController extends Controller
{
    public function __construct(
        private readonly InterventionService $service,
        private readonly InterventionMediaService $mediaService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Intervention::class);

        $user = request()->user();

        $query = Intervention::query()
            ->with([
                'intervenant:id,first_name,last_name',
                'beneficiary:id,first_name,last_name',
                'structure:id,name',
            ])
            ->orderByDesc('planned_date')
            ->orderBy('planned_start_time');

        // Intervenants only see their own interventions.
        $scopedToOwn = $user->hasPermissionTo('interventions.view.own')
            && ! $user->hasPermissionTo('interventions.view.structure');

        if ($scopedToOwn) {
            $query->where('intervenant_id', $user->id);
        }

        $statutMap = [
            InterventionStatus::Planned->value => 'planifiee',
            InterventionStatus::InProgress->value => 'en_cours',
            InterventionStatus::Completed->value => 'realisee',
            InterventionStatus::Cancelled->value => 'annulee',
            InterventionStatus::Missed->value => 'non_realisee',
        ];

        $paginator = $query->paginate(20)->through(fn (Intervention $i): array => [
            'id' => $i->id,
            'initials' => mb_strtoupper(
                mb_substr($i->intervenant?->first_name ?? '?', 0, 1)
                .mb_substr($i->intervenant?->last_name ?? '', 0, 1),
            ),
            'intervenant' => trim(($i->intervenant?->first_name ?? '').' '.($i->intervenant?->last_name ?? '')),
            'beneficiaire' => trim(($i->beneficiary?->first_name ?? '').' '.($i->beneficiary?->last_name ?? '')),
            'structure' => $i->structure?->name ?? '',
            'date_heure_debut' => trim(($i->planned_date?->format('d/m/Y') ?? '').' '.substr((string) $i->planned_start_time, 0, 5)),
            'date_heure_fin' => $i->actual_end_at?->toIso8601String(),
            'duree_minutes' => $i->actual_start_at && $i->actual_end_at
                ? (int) $i->actual_start_at->diffInMinutes($i->actual_end_at)
                : null,
            'statut' => $statutMap[$i->status->value] ?? 'planifiee',
            'compte_rendu' => filled($i->report_text) ? 'oui' : null,
            'sync_offline' => $i->visit_mode === VisitMode::Mobile,
        ]);

        $statsBase = Intervention::query();
        if ($scopedToOwn) {
            $statsBase->where('intervenant_id', $user->id);
        }

        $stats = [
            'planifiees' => (clone $statsBase)->where('status', InterventionStatus::Planned->value)->count(),
            'en_cours' => (clone $statsBase)->where('status', InterventionStatus::InProgress->value)->count(),
            'realisees' => (clone $statsBase)->where('status', InterventionStatus::Completed->value)->count(),
            'annulees' => (clone $statsBase)->where('status', InterventionStatus::Cancelled->value)->count(),
        ];

        return Inertia::render('dashboard/interventions/index', [
            'interventions' => $paginator->items(),
            'total' => $paginator->total(),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Intervention::class);

        return Inertia::render('dashboard/interventions/create', [
            'options' => $this->formOptions(request()->user()->structure_id),
        ]);
    }

    public function store(StoreInterventionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('interventions.index')
            ->with('success', 'Intervention planifiée.');
    }

    public function show(Intervention $intervention): Response
    {
        $this->authorize('view', $intervention);

        $intervention->load([
            'intervenant:id,first_name,last_name,email',
            'beneficiary:id,first_name,last_name',
            'carePlan:id,title',
            'completedTasks.plannedTask',
            'photos',
            'signatures',
        ]);

        return Inertia::render('dashboard/interventions/show', [
            'intervention' => $this->presentIntervention($intervention),
        ]);
    }

    public function edit(Intervention $intervention): Response
    {
        $this->authorize('update', $intervention);

        $intervention->load(['intervenant:id,first_name,last_name', 'beneficiary:id,first_name,last_name']);

        return Inertia::render('dashboard/interventions/edit', [
            'intervention' => [
                'id' => $intervention->id,
                'intervenant_id' => $intervention->intervenant_id,
                'beneficiary_id' => $intervention->beneficiary_id,
                'care_plan_id' => $intervention->care_plan_id,
                'planned_date' => $intervention->planned_date?->format('Y-m-d'),
                'planned_start_time' => $intervention->planned_start_time
                    ? substr((string) $intervention->planned_start_time, 0, 5)
                    : null,
                'planned_end_time' => $intervention->planned_end_time
                    ? substr((string) $intervention->planned_end_time, 0, 5)
                    : null,
                'status' => $intervention->status->value,
            ],
            'options' => $this->formOptions(request()->user()->structure_id),
        ]);
    }

    /** @return array{intervenants: array<int,array{id:int,name:string}>, beneficiaries: array<int,array{id:string,name:string}>, care_plans: array<int,array{id:string,title:string,beneficiary_id:string}>} */
    private function formOptions(int $structureId): array
    {
        $intervenants = User::query()
            ->where('structure_id', $structureId)
            ->where('type', UserType::Intervenant->value)
            ->whereNull('deleted_at')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => trim($u->first_name.' '.$u->last_name),
            ])
            ->all();

        $beneficiaries = Beneficiary::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Beneficiary $b) => [
                'id' => $b->id,
                'name' => trim($b->first_name.' '.$b->last_name),
            ])
            ->all();

        $carePlans = CarePlan::query()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title', 'beneficiary_id'])
            ->map(fn (CarePlan $cp) => [
                'id' => $cp->id,
                'title' => $cp->title,
                'beneficiary_id' => $cp->beneficiary_id,
            ])
            ->all();

        return [
            'intervenants' => $intervenants,
            'beneficiaries' => $beneficiaries,
            'care_plans' => $carePlans,
        ];
    }

    private function presentIntervention(Intervention $i): array
    {
        $statutMap = [
            InterventionStatus::Planned->value => 'planifiee',
            InterventionStatus::InProgress->value => 'en_cours',
            InterventionStatus::Completed->value => 'realisee',
            InterventionStatus::Cancelled->value => 'annulee',
            InterventionStatus::Missed->value => 'non_realisee',
        ];

        return [
            'id' => $i->id,
            'intervenant' => [
                'id' => $i->intervenant?->id,
                'name' => trim(($i->intervenant?->first_name ?? '').' '.($i->intervenant?->last_name ?? '')),
                'email' => $i->intervenant?->email,
            ],
            'beneficiaire' => [
                'id' => $i->beneficiary?->id,
                'name' => trim(($i->beneficiary?->first_name ?? '').' '.($i->beneficiary?->last_name ?? '')),
            ],
            'care_plan' => $i->carePlan ? ['id' => $i->carePlan->id, 'title' => $i->carePlan->title] : null,
            'statut' => $statutMap[$i->status->value] ?? 'planifiee',
            'visit_mode' => $i->visit_mode->value,
            'planned_date' => $i->planned_date?->format('d/m/Y'),
            'planned_start_time' => $i->planned_start_time ? substr((string) $i->planned_start_time, 0, 5) : null,
            'planned_end_time' => $i->planned_end_time ? substr((string) $i->planned_end_time, 0, 5) : null,
            'actual_start_at' => $i->actual_start_at?->toIso8601String(),
            'actual_end_at' => $i->actual_end_at?->toIso8601String(),
            'duree_minutes' => $i->actual_start_at && $i->actual_end_at
                ? (int) $i->actual_start_at->diffInMinutes($i->actual_end_at)
                : null,
            'report_text' => (string) $i->report_text,
            'cancellation_reason' => $i->cancellation_reason,
            'completed_tasks' => $i->completedTasks->map(fn ($t) => [
                'id' => $t->id,
                'planned_task_description' => $t->plannedTask?->description,
                'completed_at' => $t->completed_at?->format('d/m/Y H:i'),
                'notes' => $t->notes,
            ])->all(),
            'photos' => $i->photos->map(fn ($p) => [
                'id' => $p->id,
                'original_name' => $p->original_name,
                'taken_at' => $p->created_at?->toIso8601String(),
            ])->all(),
            'signatures' => $i->signatures->map(fn ($s) => [
                'id' => $s->id,
                'signer_type' => $s->signer_type,
                'signed_at' => $s->signed_at?->toIso8601String() ?? $s->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    public function update(UpdateInterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->service->update($intervention, $request->validated());

        return back()->with('success', 'Intervention mise à jour.');
    }

    public function destroy(Intervention $intervention): RedirectResponse
    {
        $this->authorize('delete', $intervention);

        $this->service->delete($intervention);

        return redirect()->route('interventions.index')
            ->with('success', 'Intervention supprimée.');
    }

    // ── Lifecycle ──────────────────────────────────────────────────────────

    public function checkIn(Intervention $intervention): RedirectResponse
    {
        $this->authorize('checkIn', $intervention);

        $this->service->checkIn($intervention, request()->only('latitude', 'longitude'));

        return back()->with('success', 'Prise en charge démarrée.');
    }

    public function checkOut(CheckOutInterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->service->checkOut($intervention, $request->validated());

        return back()->with('success', 'Intervention clôturée.');
    }

    public function cancel(CancelInterventionRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->service->cancel($intervention, $request->validated('cancellation_reason'));

        return back()->with('success', 'Intervention annulée.');
    }

    // ── Media ──────────────────────────────────────────────────────────────

    public function storePhoto(StoreInterventionPhotoRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->mediaService->storePhoto($intervention, $request->file('photo'), $request->user());

        return back()->with('success', 'Photo ajoutée.');
    }

    public function destroyPhoto(Intervention $intervention, InterventionPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $intervention);

        abort_if($photo->intervention_id !== $intervention->id, 404);

        $this->mediaService->deletePhoto($photo);

        return back()->with('success', 'Photo supprimée.');
    }

    public function storeSignature(StoreInterventionSignatureRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->mediaService->storeSignature(
            $intervention,
            $request->validated('signature'),
            $request->validated('signer_type'),
            $request->user(),
        );

        return back()->with('success', 'Signature enregistrée.');
    }
}
