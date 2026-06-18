<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;

class StartImpersonationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_platform_admin === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // reason is required — platform admin must document why they are
            // accessing a tenant account (HDS + RGPD obligation).
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => 'La raison de l\'accès est obligatoire.',
            'reason.min' => 'La raison doit comporter au moins :min caractères.',
        ];
    }
}
