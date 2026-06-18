<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PredictionRequest;
use App\Models\User;

class PredictionRequestPolicy extends BasePolicy
{
    /** Any authenticated user in the structure can view prediction results. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PredictionRequest $predictionRequest): bool
    {
        return true; // tenant check in BasePolicy::before()
    }

    /** Only RH, coordinateurs, and dirigeants can trigger new predictions. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['rh', 'coordinateur', 'dirigeant', 'referent_qualite']);
    }
}
