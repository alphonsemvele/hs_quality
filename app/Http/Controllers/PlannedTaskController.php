<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlannedTasks\StorePlannedTaskRequest;
use App\Http\Requests\PlannedTasks\UpdatePlannedTaskRequest;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Services\PlannedTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Inertia controller for individual PlannedTask CRUD inside a care plan.
 *
 * Routes:
 *   POST   /care-plans/{carePlan}/tasks  — append a task
 *   PUT    /tasks/{task}                 — edit one task
 *   DELETE /tasks/{task}                 — soft-delete one task
 *
 * The list/show of tasks ride on the care plan show page (CarePlanResource
 * already nests PlannedTaskResource), so there's no GET here.
 */
class PlannedTaskController extends Controller
{
    public function __construct(
        private readonly PlannedTaskService $service,
    ) {}

    public function store(StorePlannedTaskRequest $request, CarePlan $carePlan): RedirectResponse
    {
        $this->service->create($carePlan, $request->validated());

        return back()->with('success', 'Tâche ajoutée au plan.');
    }

    public function update(UpdatePlannedTaskRequest $request, PlannedTask $task): RedirectResponse
    {
        $this->service->update($task, $request->validated());

        return back()->with('success', 'Tâche mise à jour.');
    }

    public function destroy(PlannedTask $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $this->service->delete($task);

        return back()->with('success', 'Tâche supprimée.');
    }

    /**
     * Re-sequence tasks of a plan from an ordered list of UUIDs.
     * Atomically updates task_order for each entry to its index in the payload.
     */
    public function reorder(Request $request, CarePlan $carePlan): RedirectResponse
    {
        $this->authorize('update', $carePlan);

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'uuid', 'distinct'],
        ]);

        $this->service->reorder($carePlan, $validated['order']);

        return back()->with('success', 'Ordre des tâches mis à jour.');
    }
}
