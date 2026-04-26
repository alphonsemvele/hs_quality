<?php

namespace App\Http\Requests\Interventions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Intervention;

class StoreInterventionSignatureRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        /** @var Intervention $intervention */
        $intervention = $this->route('intervention');

        return $this->user()->can('update', $intervention);
    }

    public function rules(): array
    {
        // Wave 1 / H8 — bound the base64 string at 1.5 MB. A signature is
        // typically a small PNG (≤200 KB raw). Without a max, an attacker
        // could POST a 100+ MB string and OOM the worker (signature is
        // base64_decode'd into memory before write).
        return [
            'signature' => ['required', 'string', 'max:1500000'],
            'signer_type' => ['required', 'string', 'in:beneficiary,intervenant'],
        ];
    }

    public function messages(): array
    {
        return [
            'signature.required' => 'La signature est obligatoire.',
            'signature.max' => 'La signature est trop volumineuse.',
            'signer_type.in' => 'Le type de signataire doit être beneficiary ou intervenant.',
        ];
    }
}
