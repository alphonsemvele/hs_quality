<?php

declare(strict_types=1);

namespace App\Http\Requests\Beneficiaries;

use App\Http\Requests\BaseFormRequest;

/**
 * Strict-scope update — only non-medical contact fields. The dossier
 * médical (allergies, médical_history, current_treatments) is NEVER
 * touched by this request: the corresponding rule keys are absent, so
 * `$request->validated()` would not contain them even if a malicious
 * client sent them.
 *
 * Used by BeneficiaryController::updateContacts on /beneficiaries/{id}/contacts.
 */
class UpdateBeneficiaryContactsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('beneficiary')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],

            'primary_doctor' => ['sometimes', 'nullable', 'string', 'max:150'],
            'primary_doctor_phone' => ['sometimes', 'nullable', 'string', 'max:30'],

            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => "L'adresse e-mail saisie n'est pas valide.",
        ];
    }
}
