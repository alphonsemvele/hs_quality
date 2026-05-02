<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctActionPlan;

class StoreQvctActionPlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QvctActionPlan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_quarter' => ['nullable', 'string', 'max:32'],
        ];
    }
}
