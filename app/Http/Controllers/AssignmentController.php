<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assignments\AttachIntervenantRequest;
use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\User;
use App\Services\IntervenantAssignmentService;
use Illuminate\Http\RedirectResponse;

/**
 * Inertia controller for IntervenantAssignment lifecycle.
 *
 * Two endpoints:
 *   - POST /beneficiaries/{beneficiary}/assignments — attach an intervenant
 *   - DELETE /assignments/{assignment} — close an active assignment
 *
 * Both delegate to IntervenantAssignmentService for the cross-structure
 * and uniqueness invariants. The service throws HttpException with the
 * correct status (422 cross-structure, 409 duplicate / already-closed)
 * which Laravel renders as a flash error on the redirect-back response.
 */
class AssignmentController extends Controller
{
    public function __construct(
        private readonly IntervenantAssignmentService $service,
    ) {}

    public function store(AttachIntervenantRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        // Tenant-bound lookup against the *beneficiary's* structure. The form
        // request's Rule::exists already scopes to the current structure;
        // pinning to $beneficiary->structure_id closes a (currently impossible)
        // edge case where the request's tenant context drifts from the
        // route-bound beneficiary.
        $intervenant = User::query()
            ->where('structure_id', $beneficiary->structure_id)
            ->findOrFail($request->validated('intervenant_id'));

        $this->service->assign(
            intervenant: $intervenant,
            beneficiary: $beneficiary,
            assignedBy: $request->user(),
            notes: $request->validated('notes'),
        );

        return back()->with('success', 'Intervenant assigné au bénéficiaire.');
    }

    public function destroy(IntervenantAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $this->service->unassign($assignment);

        return back()->with('success', 'Assignation terminée.');
    }
}
