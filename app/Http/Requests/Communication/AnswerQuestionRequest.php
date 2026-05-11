<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Http\Requests\BaseFormRequest;
use App\Models\QaAnswer;

class AnswerQuestionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QaAnswer::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:10000'],
        ];
    }
}
