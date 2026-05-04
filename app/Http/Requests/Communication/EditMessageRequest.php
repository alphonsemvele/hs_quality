<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Http\Requests\BaseFormRequest;

class EditMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the controller via $this->authorize('update', $message)
        // because the message instance lives in the route, not in the form payload.
        // The MessageService also enforces the 5-minute window invariant at the
        // service layer — defense in depth.
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:8000'],
        ];
    }
}
