<?php

declare(strict_types=1);

namespace App\Http\Requests\Gdpr;

use App\Http\Requests\BaseFormRequest;

/**
 * Trigger a GDPR (article 15 / 20) export of the requesting user's own
 * personal data. No body payload — the user is taken from auth(). Any
 * authenticated user can request their own export.
 */
class RequestDataExportRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
