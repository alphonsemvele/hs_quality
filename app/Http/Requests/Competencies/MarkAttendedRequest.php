<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;

class MarkAttendedRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Authorization is on the attendance instance via $this->authorize('update', $attendance)
        // in the controller because the policy checks the user_id ownership;
        // any auth'd user passes the form-level gate.
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
