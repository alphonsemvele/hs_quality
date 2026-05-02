<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Pac;
use Illuminate\Support\Facades\Cache;

class PacObserver
{
    public function saved(Pac $pac): void
    {
        $this->flush($pac->structure_id);
    }

    public function deleted(Pac $pac): void
    {
        $this->flush($pac->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
