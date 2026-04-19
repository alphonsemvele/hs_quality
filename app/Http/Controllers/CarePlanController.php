<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarePlans\ArchiveCarePlanRequest;
use App\Http\Requests\CarePlans\CopyCarePlanRequest;
use App\Http\Requests\CarePlans\StoreCarePlanRequest;
use App\Http\Requests\CarePlans\UpdateCarePlanRequest;
use App\Http\Resources\BeneficiaryResource;
use App\Http\Resources\CarePlanResource;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Services\CarePlanService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia admin controller for Care Plans.
 *
 * Routing split:
 *   - List + create are NESTED under a Beneficiary (a plan never exists
 *     without a beneficiary).
 *   - Show, edit, update, delete, and lifecycle actions (activate,
 *     archive, copy) are FLAT on the care_plan (the beneficiary is
 *     already known via the plan's FK).
 */
class CarePlanController extends Controller
{
    public function __construct(
        private readonly CarePlanService $service,
    ) {}

    public function indexForBeneficiary(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);
        $this->authorize('viewAny', CarePlan::class);

        $plans = CarePlan::query()
            ->where('beneficiary_id', $beneficiary->id)
            ->withCount('tasks')
            ->orderByDesc('start_date')
            ->paginate(10);

        return Inertia::render('dashboard/care-plans/index', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
            'plans' => CarePlanResource::collection($plans),
        ]);
    }

    public function createForBeneficiary(Beneficiary $beneficiary): Response
    {
        $this->authorize('view', $beneficiary);
        $this->authorize('create', CarePlan::class);

        return Inertia::render('dashboard/care-plans/create', [
            'beneficiary' => BeneficiaryResource::make($beneficiary),
        ]);
    }

    public function storeForBeneficiary(StoreCarePlanRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $this->authorize('view', $beneficiary);

        $plan = $this->service->create([
            ...$request->validated(),
            'beneficiary_id' => $beneficiary->id,
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('care-plans.show', $plan)
            ->with('success', 'Plan d\'accompagnement créé en brouillon.');
    }

    public function show(CarePlan $carePlan): Response
    {
        $this->authorize('view', $carePlan);

        $carePlan->load(['tasks', 'beneficiary', 'createdBy']);

        return Inertia::render('dashboard/care-plans/show', [
            'plan' => CarePlanResource::make($carePlan),
            'beneficiary' => BeneficiaryResource::make($carePlan->beneficiary),
        ]);
    }

    public function edit(CarePlan $carePlan): Response
    {
        $this->authorize('update', $carePlan);

        return Inertia::render('dashboard/care-plans/edit', [
            'plan' => CarePlanResource::make($carePlan),
            'beneficiary' => BeneficiaryResource::make($carePlan->beneficiary),
        ]);
    }

    public function update(UpdateCarePlanRequest $request, CarePlan $carePlan): RedirectResponse
    {
        $this->service->update($carePlan, $request->validated());

        return redirect()
            ->route('care-plans.show', $carePlan)
            ->with('success', 'Plan mis à jour.');
    }

    public function activate(CarePlan $carePlan): RedirectResponse
    {
        $this->authorize('update', $carePlan);

        $this->service->activate($carePlan);

        return redirect()
            ->route('care-plans.show', $carePlan)
            ->with('success', 'Plan activé. Tout plan précédemment actif pour ce bénéficiaire a été automatiquement archivé.');
    }

    public function archive(ArchiveCarePlanRequest $request, CarePlan $carePlan): RedirectResponse
    {
        $this->service->archive($carePlan, $request->validated('reason'));

        return redirect()
            ->route('care-plans.show', $carePlan)
            ->with('success', 'Plan archivé.');
    }

    public function copy(CopyCarePlanRequest $request, CarePlan $carePlan): RedirectResponse
    {
        $target = Beneficiary::findOrFail($request->validated('target_beneficiary_id'));

        $newPlan = $this->service->copyFromTemplate(
            source: $carePlan,
            target: $target,
            title: $request->validated('title'),
        );

        return redirect()
            ->route('care-plans.show', $newPlan)
            ->with('success', 'Plan copié vers le bénéficiaire cible en brouillon.');
    }

    public function destroy(CarePlan $carePlan): RedirectResponse
    {
        $this->authorize('delete', $carePlan);

        $beneficiaryId = $carePlan->beneficiary_id;
        $carePlan->delete();

        return redirect()
            ->route('beneficiaries.show', $beneficiaryId)
            ->with('success', 'Plan supprimé.');
    }
}
