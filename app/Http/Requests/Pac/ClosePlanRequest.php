<?php

namespace App\Http\Requests\Pac;

use App\Http\Requests\BaseFormRequest;

class ClosePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('close', $this->route('plan'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
