<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant-side onboarding checklist. The wizard markup itself lives in
 * the React page; this controller's only job is to feed it the
 * server-computed completion state so the steps reflect reality even
 * when client storage has been cleared.
 */
class OnboardingController extends Controller
{
    public function show(OnboardingService $service): Response
    {
        $snapshot = $service->snapshot(auth()->user());

        return Inertia::render('dashboard/onboarding', $snapshot);
    }
}
