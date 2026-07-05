<?php

declare(strict_types=1);

namespace App\Jobs\Gdpr;

use App\Models\DataExportRequest;
use App\Services\Gdpr\DataExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDataExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Three attempts: the GDPR clock keeps ticking, but a transient S3 hiccup shouldn't fail the request. */
    public int $tries = 3;

    public function __construct(public readonly string $requestId) {}

    public function handle(DataExportService $service): void
    {
        $request = DataExportRequest::query()
            ->withoutGlobalScopes()
            ->find($this->requestId);

        if ($request === null) {
            return;
        }

        $service->process($request);
    }
}
