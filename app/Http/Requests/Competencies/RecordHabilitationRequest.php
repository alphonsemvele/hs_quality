<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;
use App\Models\Habilitation;

class RecordHabilitationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Habilitation::class) ?? false;
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
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'evidence_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
