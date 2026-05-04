<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Http\Requests\BaseFormRequest;
use App\Models\Message;

class SendMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Message::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:8000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*.name' => ['required_with:attachments', 'string', 'max:200'],
            'attachments.*.path' => ['required_with:attachments', 'string', 'max:500'],
        ];
    }
}
