<?php

namespace App\Http\Requests\Audits;

use App\Http\Requests\BaseFormRequest;

class CancelAuditRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('audit'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
