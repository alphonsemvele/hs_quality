<?php

declare(strict_types=1);

namespace App\Http\Requests\Gdpr;

use App\Http\Requests\BaseFormRequest;

/**
 * Initiate a GDPR (article 17 — right to erasure) account deletion. The
 * deletion is NOT executed inline — a 30-day cooling-off period gives the
 * user the chance to revoke. The request only signals intent. The body
 * requires an explicit confirmation flag to dodge accidental clicks.
 */
class RequestAccountDeletionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'confirm' => ['required', 'accepted'],
        ];
    }
}
