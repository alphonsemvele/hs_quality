<?php

namespace App\Http\Requests\Assignments;

use App\Enums\UserType;
use App\Http\Requests\BaseFormRequest;
use App\Models\Beneficiary;
use Illuminate\Validation\Rule;

/**
 * Validates a request to attach an intervenant to a beneficiary.
 *
 * Authorisation pivots on the *beneficiary*: assigning who serves a
 * beneficiary is conceptually a mutation of the beneficiary's care team,
 * so we reuse `update` on the beneficiary rather than minting a new
 * permission. Cross-structure assignment is blocked at three layers:
 *   1. The exists rule scopes the intervenant to the current tenant
 *   2. BasePolicy::before() denies update on a foreign beneficiary
 *   3. IntervenantAssignmentService throws 422 if the FKs disagree
 */
class AttachIntervenantRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $beneficiary = $this->route('beneficiary');

        return $beneficiary instanceof Beneficiary
            && $this->user()->can('update', $beneficiary);
    }

    public function rules(): array
    {
        return [
            'intervenant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('structure_id', $this->currentStructureId())
                    ->where('type', UserType::Intervenant->value)
                    ->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'intervenant_id.required' => 'L\'intervenant est obligatoire.',
            'intervenant_id.exists' => 'Intervenant introuvable dans votre structure.',
            'notes.max' => 'Les notes ne peuvent pas dépasser :max caractères.',
        ];
    }
}
