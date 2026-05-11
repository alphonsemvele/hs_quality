<?php

declare(strict_types=1);

namespace App\Http\Requests\Billing;

use App\Http\Requests\BaseFormRequest;

/**
 * Self-service subscription cancellation. Spec: PHASE2_PROGRESS.md C6.
 *
 * No body payload required — the structure is resolved from the
 * tenant context (currentStructure()). Authorization gate mirrors
 * subscribe: only users with structure.configure can cancel.
 */
class CancelSubscriptionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('structure.configure') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
