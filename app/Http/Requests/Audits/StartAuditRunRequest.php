<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Http\Requests\BaseFormRequest;
use App\Models\AuditRun;

class StartAuditRunRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AuditRun::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'audit_grid_id' => ['required', 'string', 'exists:audit_grids,id'],
            'title' => ['required', 'string', 'max:255'],
            'run_date' => ['required', 'date'],
        ];
    }
}
