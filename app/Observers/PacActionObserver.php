<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PacAction;
use Illuminate\Support\Facades\Cache;

class PacActionObserver
{
    public function saved(PacAction $action): void
    {
        $this->flush($action->structure_id);
    }

    public function deleted(PacAction $action): void
    {
        $this->flush($action->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
