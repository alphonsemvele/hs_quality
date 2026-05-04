<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;
use App\Models\Certification;

class RecordCertificationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Certification::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'max:64'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'issued_on' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:issued_on'],
            'evidence_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
