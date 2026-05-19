<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Http\Requests\BaseFormRequest;

class PortalLoginRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // unauthenticated endpoint
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:160'],
            'password' => ['required', 'string'],
        ];
    }
}
