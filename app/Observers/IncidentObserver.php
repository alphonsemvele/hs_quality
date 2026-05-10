<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Incident;
use Illuminate\Support\Facades\Cache;

class IncidentObserver
{
    public function saved(Incident $incident): void
    {
        $this->flush($incident->structure_id);
    }

    public function deleted(Incident $incident): void
    {
        $this->flush($incident->structure_id);
    }

    public function restored(Incident $incident): void
    {
        $this->flush($incident->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
