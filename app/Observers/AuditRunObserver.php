<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditRun;
use Illuminate\Support\Facades\Cache;

class AuditRunObserver
{
    public function saved(AuditRun $run): void
    {
        $this->flush($run->structure_id);
    }

    public function deleted(AuditRun $run): void
    {
        $this->flush($run->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
