<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EcartGravite;
use App\Enums\Referentiel;
use App\Http\Requests\Audits\CancelAuditRequest;
use App\Http\Requests\Audits\StoreAuditRequest;
use App\Http\Requests\Audits\StoreEcartRequest;
use App\Http\Requests\Audits\UpdateAuditRequest;
use App\Models\AuditEcart;
use App\Models\AuditRun;
use App\Models\QualityAudit;
use App\Services\HASPreparationService;
use App\Services\QualityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Module 6 — HAS / AFNOR / ISO 9001 audit grids and conformity scoring.
 */
class AuditController extends Controller
{
    public function __construct(private readonly QualityAuditService $audits) {}

    public function index(): Response
    {
        $this->authorize('viewAny', QualityAudit::class);

        $audits = QualityAudit::query()
            ->withCount('ecarts as nb_ecarts')
            ->orderByDesc('date_audit')
            ->get()
            ->map(fn (QualityAudit $a) => [
                'id' => $a->id,
                'titre' => $a->titre,
                'referentiel' => $a->referentiel->value,
                'referentiel_label' => $a->referentiel->label(),
                'date_audit' => $a->date_audit?->format('Y-m-d'),
                'statut' => $a->statut->value,
                'statut_label' => $a->statut->label(),
                'score' => $a->score,
                'auditeur' => $a->auditeur,
                'nb_ecarts' => (int) $a->nb_ecarts,
            ])->all();

        $structureId = $this->currentStructureId();

        return Inertia::render('dashboard/audits/index', [
            'audits' => $audits,
            'stats' => $structureId
                ? $this->audits->statsForStructure($structureId)
                : ['total' => 0, 'en_cours' => 0, 'termines' => 0, 'score_moyen' => null],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', QualityAudit::class);

        return Inertia::render('dashboard/audits/create', [
            'referentiels' => array_map(
                fn (Referentiel $r) => ['value' => $r->value, 'label' => $r->label()],
                Referentiel::cases(),
            ),
        ]);
    }

    public function store(StoreAuditRequest $request): RedirectResponse
    {
        $audit = $this->audits->create($request->validated(), $request->user());

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Audit créé.');
    }

    public function show(QualityAudit $audit): Response
    {
        $this->authorize('view', $audit);

        $audit->load('ecarts');

        return Inertia::render('dashboard/audits/show', [
            'audit' => [
                'id' => $audit->id,
                'titre' => $audit->titre,
                'referentiel' => $audit->referentiel->value,
                'referentiel_label' => $audit->referentiel->label(),
                'description' => $audit->description,
                'date_audit' => $audit->date_audit?->format('Y-m-d'),
                'statut' => $audit->statut->value,
                'statut_label' => $audit->statut->label(),
                'score' => $audit->score,
                'auditeur' => $audit->auditeur,
                'finalized_at' => $audit->finalized_at?->format('Y-m-d H:i'),
                'cancelled_at' => $audit->cancelled_at?->format('Y-m-d H:i'),
                'cancellation_reason' => $audit->cancellation_reason,
                'is_terminal' => $audit->isTerminal(),
                'ecarts' => $audit->ecarts->map(fn (AuditEcart $e) => [
                    'id' => $e->id,
                    'critere' => $e->critere,
                    'constat' => $e->constat,
                    'gravite' => $e->gravite->value,
                    'gravite_label' => $e->gravite->label(),
                    'action_corrective' => $e->action_corrective,
                ])->all(),
                'created_at' => $audit->created_at?->format('Y-m-d H:i'),
            ],
            'gravites' => array_map(
                fn (EcartGravite $g) => ['value' => $g->value, 'label' => $g->label()],
                EcartGravite::cases(),
            ),
            'can' => [
                'execute' => request()->user()?->can('execute', $audit) ?? false,
                'finalize' => request()->user()?->can('finalize', $audit) ?? false,
                'cancel' => request()->user()?->can('cancel', $audit) ?? false,
                'update' => request()->user()?->can('update', $audit) ?? false,
            ],
        ]);
    }

    public function edit(QualityAudit $audit): Response
    {
        $this->authorize('update', $audit);

        return Inertia::render('dashboard/audits/edit', [
            'audit' => [
                'id' => $audit->id,
                'titre' => $audit->titre,
                'referentiel' => $audit->referentiel->value,
                'description' => $audit->description,
                'date_audit' => $audit->date_audit?->format('Y-m-d'),
                'auditeur' => $audit->auditeur,
            ],
            'referentiels' => array_map(
                fn (Referentiel $r) => ['value' => $r->value, 'label' => $r->label()],
                Referentiel::cases(),
            ),
        ]);
    }

    public function update(UpdateAuditRequest $request, QualityAudit $audit): RedirectResponse
    {
        $this->audits->update($audit, $request->validated());

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Audit mis à jour.');
    }

    public function storeEcart(StoreEcartRequest $request, QualityAudit $audit): RedirectResponse
    {
        $this->audits->addEcart($audit, $request->validated(), $request->user());

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Écart ajouté.');
    }

    public function destroyEcart(QualityAudit $audit, AuditEcart $ecart): RedirectResponse
    {
        $this->authorize('execute', $audit);
        $this->audits->deleteEcart($audit, $ecart);

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Écart supprimé.');
    }

    public function finaliser(QualityAudit $audit): RedirectResponse
    {
        $this->authorize('finalize', $audit);
        $this->audits->finalize($audit);

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Audit finalisé.');
    }

    public function cancel(CancelAuditRequest $request, QualityAudit $audit): RedirectResponse
    {
        $this->audits->cancel($audit, $request->input('reason'));

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Audit annulé.');
    }

    private function currentStructureId(): ?string
    {
        return currentStructure()?->getKey();
    }

    /**
     * HAS preparation guide — gap analysis on the latest finalised HAS run
     * (or a specific one via ?run_id). Powered by HASPreparationService.
     */
    public function hasPreparation(Request $request, HASPreparationService $service): Response
    {
        $this->authorize('viewAny', AuditRun::class);

        $runs = AuditRun::query()
            ->where('status', 'finalised')
            ->whereHas('grid', fn ($q) => $q->where('source', 'has'))
            ->with(['grid:id,title,source'])
            ->orderByDesc('finalised_at')
            ->limit(10)
            ->get();

        $selected = null;
        $analysis = null;
        $runId = $request->string('run_id')->toString();
        if ($runId !== '') {
            $selected = $runs->firstWhere('id', $runId) ?? AuditRun::query()->whereKey($runId)->first();
        } elseif ($runs->isNotEmpty()) {
            $selected = $runs->first();
        }

        if ($selected) {
            try {
                $analysis = $service->analyse($selected);
            } catch (\Throwable $e) {
                $analysis = ['error' => $e->getMessage()];
            }
        }

        return Inertia::render('dashboard/audits/has-preparation', [
            'runs' => $runs->map(fn (AuditRun $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'finalised_at' => $r->finalised_at?->toIso8601String(),
            ])->all(),
            'selected_run_id' => $selected?->id,
            'analysis' => $analysis,
        ]);
    }
}
