<?php

namespace App\Http\Controllers;

use App\Http\Requests\Incidents\AnalyseIncidentRequest;
use App\Http\Requests\Incidents\AssignIncidentRequest;
use App\Http\Requests\Incidents\CloseIncidentRequest;
use App\Http\Requests\Incidents\StoreActionCorrectiveRequest;
use App\Http\Requests\Incidents\StoreIncidentRequest;
use App\Http\Requests\Incidents\UpdateIncidentRequest;
use App\Models\Incident;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function __construct(private readonly IncidentService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Incident::class);

        $user = request()->user();

        $query = Incident::query()
            ->with(['declarant:id,first_name,last_name', 'assignee:id,first_name,last_name'])
            ->orderByDesc('occurred_at');

        if ($user->hasPermissionTo('incidents.view.own') && ! $user->hasPermissionTo('incidents.view.structure')) {
            $query->where('declared_by', $user->id);
        }

        $stats = [
            'declare' => Incident::query()->where('statut', 'declare')->count(),
            'en_analyse' => Incident::query()->where('statut', 'en_analyse')->count(),
            'plan_actions' => Incident::query()->where('statut', 'plan_actions')->count(),
            'clos' => Incident::query()->where('statut', 'clos')->count(),
            'graves' => Incident::query()->whereIn('gravite', ['grave', 'critique'])->count(),
        ];

        return Inertia::render('dashboard/incidents/index', [
            'incidents' => $query->paginate(20),
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Incident::class);

        return Inertia::render('dashboard/incidents/create');
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $this->service->declare($request->validated(), $request->user());

        return redirect()->route('incidents.index')
            ->with('success', 'Incident déclaré.');
    }

    public function show(Incident $incident): Response
    {
        $this->authorize('view', $incident);

        $incident->load([
            'declarant:id,first_name,last_name',
            'assignee:id,first_name,last_name',
            'beneficiary:id,first_name,last_name',
            'actionsCorrectives.responsable:id,first_name,last_name',
            'suivis.author:id,first_name,last_name',
        ]);

        return Inertia::render('dashboard/incidents/show', [
            'incident' => $incident,
        ]);
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $this->service->update($incident, $request->validated());

        return back()->with('success', 'Incident mis à jour.');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $this->authorize('delete', $incident);

        $this->service->delete($incident);

        return redirect()->route('incidents.index')
            ->with('success', 'Incident supprimé.');
    }

    // ── Workflow ───────────────────────────────────────────────────────────

    public function assign(AssignIncidentRequest $request, Incident $incident): RedirectResponse
    {
        // Tenant-bound lookup. The form request already gates via Rule::exists
        // scoped to the current structure, but User does not carry the
        // BelongsToStructure global scope (login must find users before the
        // tenant is bound), so we re-assert the structure constraint here for
        // defense-in-depth — keeps a future relaxed validation rule from
        // becoming a cross-tenant assign.
        $coordinateur = User::query()
            ->where('structure_id', $incident->structure_id)
            ->findOrFail($request->validated('coordinateur_id'));

        $this->service->assign($incident, $coordinateur);

        return back()->with('success', 'Incident assigné.');
    }

    public function analyse(AnalyseIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $this->service->launchAnalysis($incident, $request->validated('analyse_causes'));

        return back()->with('success', 'Analyse enregistrée, plan d\'actions en cours.');
    }

    public function close(CloseIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $this->service->close($incident, $request->validated('closing_note'), $request->user());

        return back()->with('success', 'Incident clos.');
    }

    // ── Corrective actions ─────────────────────────────────────────────────

    public function storeAction(StoreActionCorrectiveRequest $request, Incident $incident): RedirectResponse
    {
        $this->service->addCorrectiveAction($incident, $request->validated());

        return back()->with('success', 'Action corrective ajoutée.');
    }
}
