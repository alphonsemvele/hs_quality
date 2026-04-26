<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Structures;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Structure;
use Illuminate\Validation\Rule;

/**
 * Validate PUT /admin/structures/{structure}. Tier changes are accepted
 * here for convenience but enforced via the separate `changeTier` policy
 * gate on the controller side — keeps the audit trail explicit when tier
 * changes happen via the dedicated endpoint instead.
 */
class UpdateStructureRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $structure = $this->route('structure');

        return $structure instanceof Structure
            && $this->user()?->can('update', $structure) === true;
    }

    public function rules(): array
    {
        $structureId = $this->route('structure')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('structures', 'code')->ignore($structureId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(StructureType::class)],
            'tier' => ['sometimes', Rule::enum(StructureTier::class)],
            'status' => ['sometimes', Rule::enum(StructureStatus::class)],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'siret' => ['sometimes', 'nullable', 'string', 'size:14', 'regex:/^\d{14}$/'],
        ];
    }
}
