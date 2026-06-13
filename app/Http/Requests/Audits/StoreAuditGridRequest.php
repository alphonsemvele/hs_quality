<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Http\Requests\BaseFormRequest;
use App\Models\AuditGrid;

class StoreAuditGridRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AuditGrid::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le nom du référentiel est obligatoire.',
        ];
    }
}
