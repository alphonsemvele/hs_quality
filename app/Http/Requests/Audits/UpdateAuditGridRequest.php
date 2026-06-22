<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Http\Requests\BaseFormRequest;

class UpdateAuditGridRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('auditGrid'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le nom du référentiel est obligatoire.',
        ];
    }
}
