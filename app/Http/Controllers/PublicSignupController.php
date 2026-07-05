<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PublicSignupRequest;
use App\Services\StructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public-facing self-serve trial signup. A brand-new structure plus its
 * first dirigeant are provisioned via StructureService::provision, which
 * also fires the welcome email containing the password-reset link the
 * dirigeant uses to choose their first password.
 *
 * The dirigeant never picks their password on the form — both because we
 * don't want to display a strong-enough hash to bots, and because Fortify
 * already owns the password lifecycle through its reset broker.
 */
class PublicSignupController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('marketing/signup');
    }

    public function store(PublicSignupRequest $request, StructureService $service): RedirectResponse
    {
        $validated = $request->validated();

        $code = mb_strtoupper(Str::random(8));

        $service->provision(
            structureData: [
                'code' => $code,
                'name' => $validated['structure_name'],
                // UI displays the regulatory acronym in uppercase; the enum
                // backing values are lowercase, so normalise on the way in.
                'type' => mb_strtolower($validated['structure_type']),
                'address' => $validated['address'] ?? null,
                'siret' => $validated['siret'],
            ],
            dirigeantData: [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ],
        );

        return redirect()
            ->route('signup.confirmation')
            ->with('signup_email', $validated['email']);
    }

    public function confirmation(): Response
    {
        return Inertia::render('marketing/signup-confirmation', [
            'email' => session('signup_email'),
        ]);
    }
}
