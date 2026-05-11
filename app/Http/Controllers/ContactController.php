<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public contact / pilot-request form. Unauthenticated. Throttled by the
 * `contact-form` rate limiter (5/min per IP — defined in routes/web.php
 * registration).
 */
class ContactController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('marketing/contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        // Persist as a structured log entry. A future iteration can pipe this
        // into Slack / a CRM / a Mailable. Keep PII minimal in the log line
        // itself — the structured `context` carries the details.
        Log::channel('stack')->info('contact.submission', [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'structure_name' => $payload['structure_name'],
            'structure_type' => $payload['structure_type'],
            'team_size' => $payload['team_size'],
            'message' => $payload['message'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('contact.show')
            ->with('success', 'Merci pour votre message. Notre équipe vous recontacte sous 24 h ouvrées.');
    }
}
