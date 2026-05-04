<?php

declare(strict_types=1);

namespace App\Http\Requests\Competencies;

use App\Http\Requests\BaseFormRequest;
use App\Models\TrainingPlan;

class DraftTrainingPlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TrainingPlan::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'theme' => ['required', 'string', 'max:200'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
