<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctIndicator;

class UpdateQvctIndicatorRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $indicator = $this->route('indicator');

        return $indicator instanceof QvctIndicator
            && $this->user()?->can('update', $indicator);
    }

    public function rules(): array
    {
        return [
            'absenteeism_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'turnover_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'work_accidents_count' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'absenteeism_rate.max' => 'Le taux d\'absentéisme ne peut pas dépasser 100 %.',
            'turnover_rate.max' => 'Le taux de turnover ne peut pas dépasser 100 %.',
        ];
    }
}
