<?php

namespace App\Http\Requests\Pac;

use App\Http\Requests\BaseFormRequest;

class CancelPlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('plan'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
