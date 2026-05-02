<?php

namespace App\Http\Controllers;

use App\Http\Requests\Interventions\CancelInterventionRequest;
use App\Http\Requests\Interventions\CheckOutInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionPhotoRequest;
use App\Http\Requests\Interventions\StoreInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionSignatureRequest;
use App\Http\Requests\Interventions\SubmitInterventionReportRequest;
use App\Http\Requests\Interventions\UpdateInterventionRequest;
use App\Models\Intervention;
use App\Models\InterventionPhoto;
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
            ->with(['intervenant:id,first_name,last_name', 'beneficiary:id,first_name,last_name'])
            ->orderByDesc('planned_date')
            ->orderBy('planned_start_time');

        // Intervenants only see their own interventions.
        if ($user->hasPermissionTo('interventions.view.own') && ! $user->hasPermissionTo('interventions.view.structure')) {
            $query->where('intervenant_id', $user->id);
        }

        return Inertia::render('dashboard/interventions/index', [
            'interventions' => $query->paginate(20),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Intervention::class);

        return Inertia::render('dashboard/interventions/create');
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

        $intervention->load(['intervenant', 'beneficiary', 'carePlan', 'completedTasks.plannedTask']);

        return Inertia::render('dashboard/interventions/show', [
            'intervention' => $intervention,
        ]);
    }

    public function edit(Intervention $intervention): Response
    {
        $this->authorize('update', $intervention);

        return Inertia::render('dashboard/interventions/edit', [
            'intervention' => $intervention,
        ]);
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

    public function submitReport(SubmitInterventionReportRequest $request, Intervention $intervention): RedirectResponse
    {
        $this->service->submitReport(
            $intervention,
            $request->validated('report_text'),
            $request->user(),
        );

        return back()->with('success', 'Compte-rendu enregistré.');
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
