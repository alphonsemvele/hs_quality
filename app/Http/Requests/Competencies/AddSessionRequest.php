<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;
use App\Models\TrainingSession;

class AddSessionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TrainingSession::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'trainer_name' => ['nullable', 'string', 'max:200'],
            'trainer_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:200'],
        ];
    }
}
