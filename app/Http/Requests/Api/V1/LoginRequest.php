<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            // Block A / #55 — optional 6-digit TOTP. Required for users
            // whose role mandates MFA AND who have already enrolled.
            // The controller checks both branches; we just validate the
            // shape here.
            'totp_code' => ['nullable', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => "L'adresse e-mail est obligatoire.",
            'email.email' => "L'adresse e-mail n'est pas valide.",
            'password.required' => 'Le mot de passe est obligatoire.',
            'totp_code.digits' => 'Le code TOTP doit contenir exactement 6 chiffres.',
        ];
    }
}
