<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctExchangeRequest;

class ScheduleExchangeRequestRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        // Route binds to {exchangeRequest} — avoid Laravel's reserved `request`.
        $exchangeRequest = $this->route('exchangeRequest');

        return $exchangeRequest instanceof QvctExchangeRequest
            && $this->user()?->can('schedule', $exchangeRequest);
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'La date du rendez-vous doit être dans le futur.',
        ];
    }
}
