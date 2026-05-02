<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Http\Requests\BaseFormRequest;
use App\Models\AuditRunResponse;

class RecordAuditResponseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AuditRunResponse::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'audit_grid_item_id' => ['required', 'string', 'exists:audit_grid_items,id'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'evidence_url' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
