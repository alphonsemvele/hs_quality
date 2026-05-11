<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Public landing-page contact form. Unauthenticated submissions OK; protected
 * server-side by validation + the contact route's rate limiter.
 *
 * The `structure_type` enum mirrors the CDC §3.2 cible list so leads route to
 * the right vertical-specific onboarding template.
 */
class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'structure_name' => ['required', 'string', 'max:160'],
            'structure_type' => ['required', 'string', 'in:saad,ssiad,spasad,esad,mandataire,ccas,autre'],
            'team_size' => ['required', 'string', 'in:1-10,11-50,51-200,200+'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Vous devez accepter notre politique de confidentialité pour nous contacter.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'structure_type.in' => 'Type de structure invalide.',
            'team_size.in' => 'Taille d\'équipe invalide.',
        ];
    }
}
