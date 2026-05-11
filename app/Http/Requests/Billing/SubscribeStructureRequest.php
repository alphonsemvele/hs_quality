<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Http\Requests\BaseFormRequest;

/**
 * Subscribe / upgrade a structure to a paid tier. Spec:
 * PHASE2_PROGRESS.md C1.
 *
 * Authorization: only users with `structure.configure` (dirigeant +
 * platform admins) can change the structure's billing state. The
 * intervenant or coordinateur shouldn't be able to enroll the
 * organisation in a paid plan they didn't authorise.
 *
 * Stripe payment-method IDs always start with `pm_` — validated via
 * a starts_with rule plus a length cap to dodge obvious garbage.
 */
class SubscribeStructureRequest extends BaseFormRequest
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
            'payment_method_id' => ['required', 'string', 'starts_with:pm_', 'max:64'],
        ];
    }
}
