<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AnnualReport;
use App\Models\User;

class AnnualReportPolicy extends BasePolicy
{
    /** Dirigeants and référents qualité can view and request annual reports. */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['dirigeant', 'referent_qualite']);
    }

    public function view(User $user, AnnualReport $report): bool
    {
        return $user->hasAnyRole(['dirigeant', 'referent_qualite']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['dirigeant', 'referent_qualite']);
    }
}
