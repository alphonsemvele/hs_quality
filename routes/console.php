<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Every domain cron lives here. Workers must be started with
| `php artisan schedule:work` (dev) or via systemd / supervisord (prod).
*/

// Block A / #54 — sweep planned interventions that aged past their
// planned end time without check-in. 15-minute cadence is fine: the
// grace window inside the command (default 30min) absorbs late
// check-ins, so the worst delay before "missed" is <45min total.
Schedule::command('interventions:sweep-missed')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->name('interventions.sweep-missed');

// Phase 2 / M3 — monthly QVCT indicator snapshot per structure.
// Runs on the 1st of every month at 03:00 local time. Idempotent
// (re-runs update auto-computed columns in place) so a missed
// month can be backfilled by re-running manually with a date.
Schedule::command('qvct:indicators:snapshot')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->name('qvct.indicators.snapshot');

// Phase 2 / M5 — daily certification expiry sweep. Idempotent: each
// cert advances forward through the windowing ladder (T-90 → T-30 →
// T-7 → expired) at most once per window, so re-running on the same
// day fires no extra alerts. 03:00 Europe/Paris keeps the job out of
// the morning planning window for coordinateurs.
Schedule::command('certifications:expiry-sweep')
    ->dailyAt('03:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->name('certifications.expiry-sweep');

// Phase 2 / E4 — quarterly access review CSV export. Runs on the 1st
// of each quarter (Jan / Apr / Jul / Oct) at 04:00 Europe/Paris.
// Output to storage/app/compliance/access-review-{date}.csv.
// Idempotent: re-running the same day overwrites. Historical exports
// are retained by the deployment platform's backup policy for the
// CISO / compliance audit trail.
Schedule::command('access-review:export')
    ->quarterly()
    ->at('04:00')
    ->timezone('Europe/Paris')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground()
    ->name('access-review.export');
