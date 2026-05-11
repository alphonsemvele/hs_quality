<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctCampaignStatus;
use App\Jobs\NotifyReferentRhJob;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Orchestrates the QVCT campaign lifecycle: launch a questionnaire as
 * a campaign, accept anonymous responses while the campaign is open,
 * close the campaign and emit weak signals via WeakSignalDetector.
 *
 * Spec: PHASE2_PROGRESS.md M3.7. Anonymity invariant: this service must
 * never persist user identity on QvctResponse. The `recordResponse`
 * method intentionally takes no User argument and never reads
 * `auth()->user()` — the only tenant binding is the structure_id from
 * the campaign.
 */
class QvctService
{
    public function __construct(
        private readonly WeakSignalDetector $detector,
    ) {}

    /**
     * Launch a draft questionnaire as a new campaign.
     *
     * @param  array{title?: string, opens_at: string, closes_at: string, target_team?: ?string}  $window
     */
    public function launchCampaign(QvctQuestionnaire $questionnaire, array $window, User $launcher): QvctCampaign
    {
        if (! $questionnaire->is_active) {
            throw new HttpException(409, 'Cannot launch a campaign from an archived questionnaire.');
        }

        return DB::transaction(function () use ($questionnaire, $window, $launcher): QvctCampaign {
            $campaign = QvctCampaign::create([
                'structure_id' => $questionnaire->structure_id,
                'questionnaire_id' => $questionnaire->id,
                'title' => $window['title'] ?? $questionnaire->title,
                'opens_at' => $window['opens_at'],
                'closes_at' => $window['closes_at'],
                'status' => QvctCampaignStatus::Active->value,
                'target_team' => $window['target_team'] ?? null,
                'launched_by' => $launcher->id,
            ]);

            return $campaign->fresh();
        });
    }

    /**
     * Record an anonymous response to an open campaign.
     *
     * Anonymity contract: the caller (controller / sync op) MUST NOT pass
     * a user_id; this signature has no User parameter by design. The
     * answers payload is opaque JSON validated against the questionnaire
     * shape at the request layer.
     *
     * @param  array<string, mixed>  $answers
     */
    public function recordResponse(QvctCampaign $campaign, array $answers, ?string $teamTag = null): QvctResponse
    {
        if (! $campaign->isOpen()) {
            throw new HttpException(409, 'Cannot submit a response to a closed or draft campaign.');
        }

        return QvctResponse::create([
            'structure_id' => $campaign->structure_id,
            'campaign_id' => $campaign->id,
            'answers' => $answers,
            'team_tag' => $teamTag,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Close the campaign and run weak-signal detection.
     *
     * Idempotent: closing an already-closed campaign re-runs detection
     * (cheap; useful when the detector logic changes and a tenant wants
     * to re-emit signals). Status timestamps are not overwritten on
     * second-run.
     */
    public function closeCampaign(QvctCampaign $campaign): QvctCampaign
    {
        return DB::transaction(function () use ($campaign): QvctCampaign {
            if ($campaign->status !== QvctCampaignStatus::Closed) {
                $campaign->update([
                    'status' => QvctCampaignStatus::Closed->value,
                    'closed_at' => now(),
                ]);
            }

            $emitted = $this->detector->detectFor($campaign->fresh());

            // One alert per emitted signal — RH gets fan-out per
            // (team × signal_type) so they can route triage efficiently.
            // afterCommit so a transaction rollback doesn't leave alerts
            // pointing at signals that don't exist.
            foreach ($emitted as $signal) {
                NotifyReferentRhJob::dispatch($signal)->afterCommit();
            }

            return $campaign->fresh();
        });
    }
}
