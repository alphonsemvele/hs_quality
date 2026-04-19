<?php

namespace App\Http\Controllers;

use App\Http\Requests\Beneficiaries\StoreBeneficiaryRequest;
use App\Http\Requests\Beneficiaries\UpdateBeneficiaryRequest;
use App\Http\Resources\BeneficiaryDossierResource;
use App\Http\Resources\BeneficiaryResource;
use App\Models\Beneficiary;
use App\Services\BeneficiaryService;
use Illuminate\Http\RedirectResponse;
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

        $beneficiaries = Beneficiary::query()
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

        return Inertia::render('dashboard/beneficiaries/show', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
        ]);
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
}
