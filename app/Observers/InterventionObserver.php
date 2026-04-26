<?php

namespace App\Observers;

use App\Models\Intervention;
use Illuminate\Support\Facades\Cache;

class InterventionObserver
{
    public function saved(Intervention $intervention): void
    {
        $this->flush($intervention->structure_id);
    }

    public function deleted(Intervention $intervention): void
    {
        $this->flush($intervention->structure_id);
    }

    public function restored(Intervention $intervention): void
    {
        $this->flush($intervention->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
