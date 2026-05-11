<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Training plan lifecycle. Spec: PHASE2_PROGRESS.md M5.3 / M5.10.
 *
 *   Draft     — being authored; sessions can be added/edited freely
 *   Published — sessions are visible to intervenants; can still add
 *               sessions but the plan itself is locked from re-titling
 *   Archived  — historical record; no further mutations
 */
enum TrainingPlanStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
