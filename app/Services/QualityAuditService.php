<?php

namespace App\Services;

use App\Enums\AuditStatus;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Models\AuditEcart;
use App\Models\PlanAmelioration;
use App\Models\QualityAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Module 6 — Audits qualité.
 *
 * Owns the lifecycle (planifie → en_cours → termine | annule), score
 * computation from the écarts list, and the écart → PAC cascade.
 *
 * Score formula: 100 - sum(weight × écart_count) / max_score × 100,
 * floored at 0. Mineur weight = 1, majeur = 3, critique = 5. The
 * "max" baseline is 20 weighted points — past 20 points of écarts the
 * audit scores 0 regardless. This is intentionally simple; structures
 * tune it later via configuration if needed.
 */
class QualityAuditService
{
    private const SCORE_BASELINE = 20;

    public function create(array $data, User $createdBy): QualityAudit
    {
        $data['created_by'] = $createdBy->id;
        $data['statut'] ??= AuditStatus::Planifie->value;

        return QualityAudit::create($data);
    }

    public function update(QualityAudit $audit, array $data): QualityAudit
    {
        if ($audit->isTerminal()) {
            throw new HttpException(409, 'Cannot update a terminated audit.');
        }

        $audit->update($data);

        return $audit->fresh();
    }

    /**
     * Add an écart to the audit, optionally cascading to a PAC entry.
     *
     * If `create_pac=true` and the écart gravity requires PAC, a new
     * PlanAmelioration is created in the same transaction with
     * source=audit and source_id=audit_id, seeded with the écart info.
     * The audit is moved to en_cours if it was still planifie.
     */
    public function addEcart(QualityAudit $audit, array $data, User $by): array
    {
        if ($audit->isTerminal()) {
            throw new HttpException(409, 'Cannot add findings to a terminated audit.');
        }

        return DB::transaction(function () use ($audit, $data, $by) {
            $shouldCascade = (bool) ($data['create_pac'] ?? false);
            unset($data['create_pac']);

            $ecart = $audit->ecarts()->create($data + [
                'structure_id' => $audit->structure_id,
            ]);

            // Auto-promote to en_cours on the first écart entered.
            if ($audit->statut === AuditStatus::Planifie) {
                $audit->update(['statut' => AuditStatus::EnCours->value]);
            }

            $pac = null;

            if ($shouldCascade && $ecart->gravite->requiresPac()) {
                $pac = PlanAmelioration::create([
                    'structure_id' => $audit->structure_id,
                    'created_by' => $by->id,
                    'titre' => "Écart {$ecart->gravite->label()} — {$ecart->critere}",
                    'source' => PacSource::Audit->value,
                    'source_id' => $audit->id,
                    'constat' => $ecart->constat,
                    'responsable' => $audit->auditeur,
                    'echeance' => null,
                    'statut' => PlanAmeliorationStatus::Ouvert->value,
                ]);
            }

            return ['ecart' => $ecart->fresh(), 'pac' => $pac];
        });
    }

    public function deleteEcart(QualityAudit $audit, AuditEcart $ecart): void
    {
        if ($audit->isTerminal()) {
            throw new HttpException(409, 'Cannot remove findings from a terminated audit.');
        }
        if ($ecart->quality_audit_id !== $audit->id) {
            throw new HttpException(404);
        }

        $ecart->delete();
    }

    /**
     * Finalize the audit: compute score from écarts, lock the record.
     */
    public function finalize(QualityAudit $audit): QualityAudit
    {
        if ($audit->isTerminal()) {
            throw new HttpException(409, 'Audit already terminated.');
        }

        $score = $this->computeScore($audit);

        $audit->update([
            'statut' => AuditStatus::Termine->value,
            'finalized_at' => now(),
            'score' => $score,
        ]);

        return $audit->fresh();
    }

    public function cancel(QualityAudit $audit, ?string $reason): QualityAudit
    {
        if ($audit->isTerminal()) {
            throw new HttpException(409, 'Audit already terminated.');
        }

        $audit->update([
            'statut' => AuditStatus::Annule->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $audit->fresh();
    }

    public function computeScore(QualityAudit $audit): int
    {
        $weighted = 0;
        foreach ($audit->ecarts as $ecart) {
            $weighted += $ecart->gravite->scoreWeight();
        }
        $penalty = min(100, ($weighted / self::SCORE_BASELINE) * 100);

        return (int) max(0, round(100 - $penalty));
    }

    /** @return array{total:int, en_cours:int, termines:int, score_moyen:?int} */
    public function statsForStructure(string $structureId): array
    {
        $audits = QualityAudit::query()->where('structure_id', $structureId)->get();
        $termines = $audits->where('statut', AuditStatus::Termine);

        return [
            'total' => $audits->count(),
            'en_cours' => $audits->where('statut', AuditStatus::EnCours)->count(),
            'termines' => $termines->count(),
            'score_moyen' => $termines->isEmpty() ? null : (int) round($termines->avg('score')),
        ];
    }
}
