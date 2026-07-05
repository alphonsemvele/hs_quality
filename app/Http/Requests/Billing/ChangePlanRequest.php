<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Http\Requests\BaseFormRequest;

/**
 * Inertia plan-change surface — `POST /billing/change-plan`. Unlike
 * SubscribeStructureRequest this carries no payment_method_id: the
 * Stripe subscription already exists and Cashier's swap() reuses the
 * stored payment method. Same authorization gate as subscribe / cancel.
 */
class ChangePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('structure.configure') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tier' => ['required', 'string', 'in:essential,pro,premium'],
        ];
    }
}
