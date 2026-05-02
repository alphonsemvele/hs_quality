<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctActionPlan;

class StoreQvctActionPlanItemRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');

        return $plan instanceof QvctActionPlan
            && $this->user()?->can('update', $plan);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'impact_measurement_target' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
