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
