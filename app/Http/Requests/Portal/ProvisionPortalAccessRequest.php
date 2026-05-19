<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Http\Requests\BaseFormRequest;
use App\Models\Beneficiary;

class ProvisionPortalAccessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->route('beneficiary');

        return $this->user()?->can('update', $beneficiary) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:160'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
