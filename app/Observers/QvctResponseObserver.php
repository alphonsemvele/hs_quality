<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\QvctResponse;
use Illuminate\Support\Facades\Cache;

class QvctResponseObserver
{
    public function created(QvctResponse $response): void
    {
        $this->flush($response->structure_id);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
