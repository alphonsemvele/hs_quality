<?php

declare(strict_types=1);

namespace App\Enums;

enum FamilyTokenScope: string
{
    /** Read the beneficiary's active care plan (tasks, objectives, dates). */
    case ReadPlan = 'read_plan';

    /** Read the beneficiary's recent intervention history (dates, status — no medical reports). */
    case ReadInterventions = 'read_interventions';

    /** Submit a satisfaction rating on behalf of a family member. */
    case SubmitSatisfaction = 'submit_satisfaction';
}
