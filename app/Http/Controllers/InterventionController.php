<?php

namespace App\Http\Controllers;

use App\Http\Requests\Interventions\CancelInterventionRequest;
use App\Http\Requests\Interventions\CheckOutInterventionRequest;
use App\Http\Requests\Interventions\StoreInterventionRequest;
use App\Http\Requests\Interventions\UpdateInterventionRequest;
use App\Models\Intervention;
use App\Services\InterventionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InterventionController extends Controller
{
    public function __construct(private readonly InterventionService $service) {}

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
}
