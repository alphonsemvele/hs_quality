<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PredictionType;
use App\Http\Requests\BaseFormRequest;
use App\Models\PredictionRequest;
use Illuminate\Validation\Rule;

class StorePredictionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PredictionRequest::class) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(PredictionType::class)],
            'input_data' => ['required', 'array'],
            'input_data.*' => ['nullable'],
        ];
    }
}
