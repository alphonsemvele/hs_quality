<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Self-serve trial signup from the marketing site. Provisions a brand-new
 * structure with the requester as its first dirigeant. All authenticated
 * fields stay opt-in defaults — the user picks their password via the
 * reset link emailed at the end of the flow.
 *
 * Honeypot — the `website` field is hidden by CSS on the form; legitimate
 * humans never touch it, but naive bots dump every visible input with a
 * value. If it comes in non-empty we reject with a benign validation
 * error so the spammer can't tell they were caught.
 *
 * Authorization: anyone (signup is public by design).
 */
class PublicSignupRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'structure_name' => ['required', 'string', 'max:200'],
            'structure_type' => ['required', 'string', 'in:SAAD,SSIAD,SPASAD,ESAD,CCAS'],
            'siret' => ['required', 'string', 'regex:/^\d{14}$/'],
            'address' => ['nullable', 'string', 'max:500'],

            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],

            'accept_cgu' => ['required', 'accepted'],

            // Honeypot — must be empty.
            'website' => ['nullable', 'string', 'size:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'siret.regex' => 'Le numéro SIRET doit comporter 14 chiffres.',
            'accept_cgu.accepted' => 'Vous devez accepter les CGU pour créer un compte.',
            'website.size' => 'Vérification anti-spam échouée.',
        ];
    }
}
