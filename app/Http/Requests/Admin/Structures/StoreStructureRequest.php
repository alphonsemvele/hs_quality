<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Structures;

use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Structure;
use Illuminate\Validation\Rule;

/**
 * Validate POST /admin/structures — provisioning a new tenant + initial
 * dirigeant. Authorisation is via the StructurePolicy::create gate.
 */
class StoreStructureRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Structure::class) === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('structures', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(StructureType::class)],
            'tier' => ['nullable', Rule::enum(StructureTier::class)],
            'address' => ['nullable', 'string', 'max:1000'],
            'siret' => ['nullable', 'string', 'size:14', 'regex:/^\d{14}$/'],

            'dirigeant.first_name' => ['required', 'string', 'max:100'],
            'dirigeant.last_name' => ['required', 'string', 'max:100'],
            'dirigeant.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'dirigeant.phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code de structure existe déjà.',
            'siret.size' => 'Le SIRET doit contenir exactement 14 chiffres.',
            'siret.regex' => 'Le SIRET ne doit contenir que des chiffres.',
            'dirigeant.email.unique' => 'Un utilisateur avec cet email existe déjà.',
        ];
    }
}
