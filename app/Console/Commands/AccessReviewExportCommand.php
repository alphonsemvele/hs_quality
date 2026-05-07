<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AccessReviewExportService;
use Illuminate\Console\Command;

/**
 * Phase 2 / E4 — Quarterly access review CSV exporter.
 *
 * Cron schedule: routes/console.php → quarterly at 04:00 Europe/Paris.
 *
 * Usage:
 *   php artisan access-review:export
 *   php artisan access-review:export --structure=<uuid>
 *   php artisan access-review:export --output=/tmp/review.csv
 *
 * Output: one row per (structure, user) with effective Spatie roles +
 * permissions resolved under the tenant team context. CSV columns are
 * stable; new columns are appended (never inserted) to keep historical
 * exports diffable.
 *
 * Idempotent: re-running on the same day overwrites the file. Compliance
 * keeps the file under storage/app/compliance/ — the deployment
 * platform's backup policy retains historical exports.
 */
class AccessReviewExportCommand extends Command
{
    protected $signature = 'access-review:export
        {--structure= : Filter to a single structure UUID}
        {--output= : Output CSV path (default: storage/app/compliance/access-review-{YYYY-MM-DD}.csv)}';

    protected $description = 'Export per-user effective permissions per structure for quarterly access review (CDC compliance / Phase 2 E4).';

    public function handle(AccessReviewExportService $service): int
    {
        $output = $this->option('output') ?? $this->defaultPath();
        $this->ensureDirectoryExists(dirname($output));

        $handle = fopen($output, 'w');
        if ($handle === false) {
            $this->error("Cannot open output file for writing: {$output}");

            return self::FAILURE;
        }

        $headersWritten = false;
        $rowCount = 0;

        foreach ($service->rows($this->option('structure')) as $row) {
            if (! $headersWritten) {
                fputcsv($handle, array_keys($row));
                $headersWritten = true;
            }
            fputcsv($handle, array_values($row));
            $rowCount++;
        }

        fclose($handle);

        $this->info("Access review exported: {$output}");
        $this->info("Rows: {$rowCount}");

        return self::SUCCESS;
    }

    private function defaultPath(): string
    {
        return storage_path('app/compliance/access-review-'.now()->format('Y-m-d').'.csv');
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
