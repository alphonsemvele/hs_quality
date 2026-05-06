<?php

namespace App\Services;

use App\Enums\ActionStatus;
use App\Enums\PlanAmeliorationStatus;
use App\Models\ActionAmelioration;
use App\Models\PlanAmelioration;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PlanAmeliorationService
{
    public function create(array $data, User $createdBy): PlanAmelioration
    {
        $data['created_by'] = $createdBy->id;
        $data['statut'] ??= PlanAmeliorationStatus::Ouvert->value;

        return PlanAmelioration::create($data);
    }

    public function update(PlanAmelioration $plan, array $data): PlanAmelioration
    {
        if ($plan->isTerminal()) {
            throw new HttpException(409, 'Cannot update a closed PAC.');
        }
        $plan->update($data);

        return $plan->fresh();
    }

    public function addAction(PlanAmelioration $plan, array $data): ActionAmelioration
    {
        if ($plan->isTerminal()) {
            throw new HttpException(409, 'Cannot add actions to a closed PAC.');
        }

        $action = $plan->actions()->create($data + [
            'structure_id' => $plan->structure_id,
            'statut' => ActionStatus::Planifiee->value,
        ]);

        // Auto-promote ouvert → en_cours when the first action is added.
        if ($plan->statut === PlanAmeliorationStatus::Ouvert) {
            $plan->update(['statut' => PlanAmeliorationStatus::EnCours->value]);
        }

        return $action->fresh();
    }

    public function updateAction(ActionAmelioration $action, array $data): ActionAmelioration
    {
        if ($action->plan->isTerminal()) {
            throw new HttpException(409, 'Parent PAC is closed.');
        }

        // realise_at is set when the user transitions to the realisée status,
        // cleared when reverting to a non-done status. Keeping the timestamp
        // server-side avoids client clock drift.
        if (isset($data['statut'])) {
            $next = ActionStatus::from((string) $data['statut']);
            $data['realise_at'] = $next->isDone() ? now() : null;
        }

        $action->update($data);

        return $action->fresh();
    }

    public function markActionDone(ActionAmelioration $action): ActionAmelioration
    {
        return $this->updateAction($action, ['statut' => ActionStatus::Realisee->value]);
    }

    public function deleteAction(ActionAmelioration $action): void
    {
        if ($action->plan->isTerminal()) {
            throw new HttpException(409, 'Parent PAC is closed.');
        }
        $action->delete();
    }

    public function close(PlanAmelioration $plan, ?string $reason): PlanAmelioration
    {
        if ($plan->isTerminal()) {
            throw new HttpException(409, 'PAC already terminated.');
        }
        $plan->update([
            'statut' => PlanAmeliorationStatus::Termine->value,
            'closed_at' => now(),
        ]);

        return $plan->fresh();
    }

    public function cancel(PlanAmelioration $plan, ?string $reason): PlanAmelioration
    {
        if ($plan->isTerminal()) {
            throw new HttpException(409, 'PAC already terminated.');
        }
        $plan->update([
            'statut' => PlanAmeliorationStatus::Annule->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $plan->fresh();
    }

    /** @return array{total:int, en_cours:int, termines:int, taux_completion:?int} */
    public function statsForStructure(string $structureId): array
    {
        $plans = PlanAmelioration::query()
            ->where('structure_id', $structureId)
            ->withCount([
                'actions as actions_total' => fn ($q) => $q->where('statut', '!=', ActionStatus::Annulee->value),
                'actions as actions_done' => fn ($q) => $q->where('statut', ActionStatus::Realisee->value),
            ])->get();

        $totalActions = (int) $plans->sum('actions_total');
        $doneActions = (int) $plans->sum('actions_done');

        return [
            'total' => $plans->count(),
            'en_cours' => $plans->where('statut', PlanAmeliorationStatus::EnCours)->count(),
            'termines' => $plans->where('statut', PlanAmeliorationStatus::Termine)->count(),
            'taux_completion' => $totalActions === 0
                ? null
                : (int) round(($doneActions / $totalActions) * 100),
        ];
    }
}
