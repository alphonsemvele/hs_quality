<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;
use App\Models\TrainingAttendance;

class RegisterAttendanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TrainingAttendance::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // user_id is optional — when absent, the controller registers
            // the authenticated user (self-service path). RH/coord can
            // register someone else by passing user_id.
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
