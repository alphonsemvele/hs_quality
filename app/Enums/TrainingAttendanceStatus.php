<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Training attendance lifecycle. Spec: PHASE2_PROGRESS.md M5.5 / M5.10.
 *
 *   Registered — user signed up for the session, hasn't attended yet
 *   Attended   — confirmed attendance (set by trainer/RH after the session)
 *   Cancelled  — user dropped out OR session was cancelled by the structure
 */
enum TrainingAttendanceStatus: string
{
    case Registered = 'registered';
    case Attended = 'attended';
    case Cancelled = 'cancelled';
}
