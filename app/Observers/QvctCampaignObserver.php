<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\QvctCampaign;
use Illuminate\Support\Facades\Cache;

/**
 * Flush dashboard cache when a campaign moves through the lifecycle —
 * the qvct_campaigns_open tile depends on `status`, so launch / close
 * needs to invalidate immediately.
 */
class QvctCampaignObserver
{
    public function saved(QvctCampaign $campaign): void
    {
        $this->flush($campaign->structure_id);
    }

    public function deleted(QvctCampaign $campaign): void
    {
        $this->flush($campaign->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
