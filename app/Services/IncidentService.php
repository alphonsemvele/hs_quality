<?php

namespace App\Services;

use App\Enums\CategorieIncident;
use App\Enums\StatutIncident;
use App\Events\IncidentDeclared;
use App\Jobs\NotifyARSJob;
use App\Jobs\NotifyResponsableSecteurJob;
use App\Models\Incident;
use App\Models\IncidentActionCorrective;
use App\Models\IncidentSuivi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IncidentService
{
    /**
     * Declare a new incident: auto-classify gravity, persist, fire event + jobs.
     *
     * Block A / #56 — supports an optional client-assigned UUID via $data['id'].
     * When provided, the call is idempotent: a network retry of the same POST
     * (same id, same tenant) returns the existing incident WITHOUT firing
     * the event or notification jobs again. This lets the offline mobile
     * app safely retry without ARS double-notification.
     */
    public function declare(array $data, User $declaredBy): Incident
    {
        $clientId = $data['id'] ?? null;

        // Replay short-circuit: if the client sent an id and a row with
        // that id already exists in this tenant, return it as-is. We
        // intentionally do NOT trust an id from a foreign tenant — the
        // global scope on Incident filters by structure_id.
        if ($clientId !== null) {
            $existing = Incident::query()
                ->where('id', $clientId)
                ->where('structure_id', $declaredBy->structure_id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            // Cross-tenant collision check. If the id exists globally
            // but in a different tenant, the PK insert below would throw
            // a constraint violation (500). Catch it earlier and return
            // 422 so the mobile client regenerates a fresh UUID.
            $foreignCollision = Incident::query()
                ->withoutGlobalScopes()
                ->where('id', $clientId)
                ->exists();

            if ($foreignCollision) {
                throw new HttpException(422, 'incident_id_already_in_use');
            }
        }

        return DB::transaction(function () use ($data, $clientId, $declaredBy): Incident {
            $categorie = CategorieIncident::from($data['categorie']);
            $gravite = GraviteClassifier::classify(
                $categorie,
                (bool) ($data['avec_deces'] ?? false),
                (bool) ($data['avec_hospitalisation'] ?? false),
                (bool) ($data['avec_blessure_physique'] ?? false),
            );

            // Strip 'id' from the spread; we set it explicitly so the
            // model uses it instead of HasUuids' auto-generated value.
            unset($data['id']);

            $incident = new Incident([
                ...$data,
                'structure_id' => $declaredBy->structure_id,
                'declared_by' => $declaredBy->id,
                'gravite' => $gravite->value,
                'statut' => StatutIncident::Declare->value,
            ]);

            if ($clientId !== null) {
                $incident->id = $clientId;
            }

            $incident->save();
            $fresh = $incident->fresh();

            IncidentDeclared::dispatch($fresh);
            NotifyResponsableSecteurJob::dispatch($fresh);

            if ($gravite->requiresARSNotification()) {
                NotifyARSJob::dispatch($fresh);
            }

            return $fresh;
        });
    }

    /**
     * Assign to a coordinateur and move to en_analyse.
     */
    public function assign(Incident $incident, User $coordinateur): Incident
    {
        if ($incident->isClosed()) {
            throw new HttpException(409, 'Impossible d\'assigner un incident clos.');
        }

        return DB::transaction(function () use ($incident, $coordinateur): Incident {
            $incident->update([
                'assigned_to' => $coordinateur->id,
                'statut' => StatutIncident::EnAnalyse->value,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Record 5-whys analysis and move to plan_actions.
     */
    public function launchAnalysis(Incident $incident, string $analyseCauses): Incident
    {
        if ($incident->isClosed()) {
            throw new HttpException(409, 'Incident déjà clos.');
        }

        if ($incident->statut !== StatutIncident::EnAnalyse) {
            throw new HttpException(409, 'L\'analyse nécessite le statut en_analyse.');
        }

        return DB::transaction(function () use ($incident, $analyseCauses): Incident {
            $incident->update([
                'analyse_causes' => $analyseCauses,
                'statut' => StatutIncident::PlanActions->value,
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Add a corrective action to the incident.
     */
    public function addCorrectiveAction(Incident $incident, array $data): IncidentActionCorrective
    {
        if ($incident->isClosed()) {
            throw new HttpException(409, 'Impossible d\'ajouter une action à un incident clos.');
        }

        return IncidentActionCorrective::create([
            ...$data,
            'structure_id' => $incident->structure_id,
            'incident_id' => $incident->id,
        ]);
    }

    /**
     * Mark a corrective action as done.
     */
    public function completeAction(IncidentActionCorrective $action): IncidentActionCorrective
    {
        $action->update(['statut' => 'done', 'realise_at' => now()]);

        return $action->fresh();
    }

    /**
     * Add a follow-up note.
     */
    public function addSuivi(Incident $incident, string $note, User $author): IncidentSuivi
    {
        return IncidentSuivi::create([
            'structure_id' => $incident->structure_id,
            'incident_id' => $incident->id,
            'author_id' => $author->id,
            'note' => $note,
        ]);
    }

    /**
     * Close the incident.
     */
    public function close(Incident $incident, string $closingNote, User $closedBy): Incident
    {
        if ($incident->isClosed()) {
            throw new HttpException(409, 'Incident déjà clos.');
        }

        return DB::transaction(function () use ($incident, $closingNote, $closedBy): Incident {
            $this->addSuivi($incident, $closingNote, $closedBy);

            $incident->update([
                'statut' => StatutIncident::Clos->value,
                'closed_at' => now(),
            ]);

            return $incident->fresh();
        });
    }

    /**
     * Apply a partial update to an incident's free-text + factual fields.
     *
     * Whitelisted in UpdateIncidentRequest — never accepts gravite, statut,
     * declared_by, assigned_to, closed_at, structure_id, ARS notification
     * timestamps, etc. Those transitions go through the dedicated workflow
     * methods (assign, launchAnalysis, close).
     *
     * Wrapped in a transaction so the API and web controllers share one
     * code path with consistent locking semantics.
     */
    public function update(Incident $incident, array $data): Incident
    {
        if ($incident->isClosed()) {
            throw new HttpException(409, 'Impossible de modifier un incident clos.');
        }

        return DB::transaction(function () use ($incident, $data): Incident {
            $incident->update($data);

            return $incident->fresh();
        });
    }

    public function delete(Incident $incident): void
    {
        $incident->delete();
    }
}
