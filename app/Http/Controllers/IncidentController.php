<?php

namespace App\Http\Controllers;

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
use App\Http\Requests\Incidents\AnalyseIncidentRequest;
use App\Http\Requests\Incidents\AssignIncidentRequest;
use App\Http\Requests\Incidents\CloseIncidentRequest;
use App\Http\Requests\Incidents\StoreActionCorrectiveRequest;
use App\Http\Requests\Incidents\StoreIncidentRequest;
use App\Http\Requests\Incidents\UpdateIncidentRequest;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\User;
use App\Services\CustomOptionService;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $service,
        private readonly CustomOptionService $options,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Incident::class);

        $user = request()->user();

        $query = Incident::query()
            ->with([
                'declarant:id,first_name,last_name',
                'assignee:id,first_name,last_name',
                'structure:id,name',
            ])
            ->orderByDesc('occurred_at');

        $scopedToOwn = $user->hasPermissionTo('incidents.view.own')
            && ! $user->hasPermissionTo('incidents.view.structure');

        if ($scopedToOwn) {
            $query->where('declared_by', $user->id);
        }

        $paginator = $query->paginate(20)->through(fn (Incident $i): array => [
            'id' => $i->id,
            'initials' => mb_strtoupper(
                mb_substr($i->declarant?->first_name ?? '?', 0, 1)
                .mb_substr($i->declarant?->last_name ?? '', 0, 1),
            ),
            'declarant' => trim(($i->declarant?->first_name ?? '').' '.($i->declarant?->last_name ?? '')),
            'categorie' => $i->categorie->label(),
            'gravite' => $i->gravite->value,
            'statut' => $i->statut->value,
            'structure' => $i->structure?->name ?? '',
            'date_heure' => $i->occurred_at?->format('d/m/Y H:i') ?? '',
            'description' => (string) $i->description,
            'notifie_responsable' => $i->notifie_responsable_at !== null,
            'notifie_autorites' => $i->notifie_ars_at !== null,
        ]);

        $statsBase = Incident::query();
        if ($scopedToOwn) {
            $statsBase->where('declared_by', $user->id);
        }

        $stats = [
            'declare' => (clone $statsBase)->where('statut', StatutIncident::Declare->value)->count(),
            'en_analyse' => (clone $statsBase)->where('statut', StatutIncident::EnAnalyse->value)->count(),
            'plan_actions' => (clone $statsBase)->where('statut', StatutIncident::PlanActions->value)->count(),
            'clos' => (clone $statsBase)->where('statut', StatutIncident::Clos->value)->count(),
            'graves' => (clone $statsBase)
                ->whereIn('gravite', [GraviteIncident::Grave->value, GraviteIncident::Critique->value])
                ->count(),
        ];

        return Inertia::render('dashboard/incidents/index', [
            'incidents' => $paginator->items(),
            'total' => $paginator->total(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Incident::class);

        $structureId = request()->user()->structure_id;

        $beneficiaries = Beneficiary::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (Beneficiary $b) => [
                'id' => $b->id,
                'name' => trim($b->first_name.' '.$b->last_name),
            ])
            ->all();

        $interventions = Intervention::query()
            ->where('structure_id', $structureId)
            ->orderByDesc('planned_date')
            ->limit(50)
            ->get(['id', 'planned_date', 'beneficiary_id'])
            ->map(fn (Intervention $i) => [
                'id' => $i->id,
                'label' => $i->planned_date?->format('d/m/Y').' — '.($i->beneficiary_id ? '#'.substr($i->beneficiary_id, 0, 8) : ''),
            ])
            ->all();

        $categoriesEnum = collect(CategorieIncident::cases())->map(fn (CategorieIncident $c) => [
            'value' => $c->value,
            'label' => $c->label(),
        ])->all();

        return Inertia::render('dashboard/incidents/create', [
            'options' => [
                'beneficiaries' => $beneficiaries,
                'interventions' => $interventions,
                'categories' => $this->options->mergeWithEnum($categoriesEnum, 'categorie_incident'),
            ],
        ]);
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
            'incident' => [
                'id' => $incident->id,
                'declarant' => [
                    'id' => $incident->declarant?->id,
                    'name' => trim(($incident->declarant?->first_name ?? '').' '.($incident->declarant?->last_name ?? '')),
                ],
                'assignee' => $incident->assignee
                    ? [
                        'id' => $incident->assignee->id,
                        'name' => trim($incident->assignee->first_name.' '.$incident->assignee->last_name),
                    ]
                    : null,
                'beneficiaire' => $incident->beneficiary
                    ? [
                        'id' => $incident->beneficiary->id,
                        'name' => trim($incident->beneficiary->first_name.' '.$incident->beneficiary->last_name),
                    ]
                    : null,
                'categorie' => $incident->categorie->label(),
                'gravite' => $incident->gravite->value,
                'statut' => $incident->statut->value,
                'description' => (string) $incident->description,
                'lieu' => $incident->lieu,
                'occurred_at' => $incident->occurred_at?->format('d/m/Y H:i'),
                'avec_deces' => (bool) $incident->avec_deces,
                'avec_hospitalisation' => (bool) $incident->avec_hospitalisation,
                'avec_blessure_physique' => (bool) $incident->avec_blessure_physique,
                'analyse_causes' => (string) $incident->analyse_causes,
                'closed_at' => $incident->closed_at?->format('d/m/Y H:i'),
                'notifie_responsable' => $incident->notifie_responsable_at !== null,
                'notifie_autorites' => $incident->notifie_ars_at !== null,
                'actions_correctives' => $incident->actionsCorrectives->map(fn ($a) => [
                    'id' => $a->id,
                    'description' => $a->description,
                    'echeance' => $a->echeance?->format('d/m/Y'),
                    'statut' => $a->statut,
                    'responsable' => $a->responsable
                        ? trim($a->responsable->first_name.' '.$a->responsable->last_name)
                        : null,
                    'realise_at' => $a->realise_at?->format('d/m/Y H:i'),
                ])->all(),
                'suivis' => $incident->suivis->map(fn ($s) => [
                    'id' => $s->id,
                    'note' => $s->note,
                    'author' => $s->author
                        ? trim($s->author->first_name.' '.$s->author->last_name)
                        : null,
                    'created_at' => $s->created_at?->format('d/m/Y H:i'),
                ])->all(),
            ],
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
