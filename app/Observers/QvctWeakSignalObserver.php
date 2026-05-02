<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\QvctWeakSignal;
use Illuminate\Support\Facades\Cache;

class QvctWeakSignalObserver
{
    public function saved(QvctWeakSignal $signal): void
    {
        $this->flush($signal->structure_id);
    }

    public function deleted(QvctWeakSignal $signal): void
    {
        $this->flush($signal->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
