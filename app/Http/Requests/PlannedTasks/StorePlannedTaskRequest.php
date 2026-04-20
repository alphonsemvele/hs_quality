<?php

namespace App\Http\Requests\PlannedTasks;

use App\Enums\TaskFrequency;
use App\Http\Requests\BaseFormRequest;
use App\Models\CarePlan;
use Illuminate\Validation\Rule;

class StorePlannedTaskRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('carePlan');

        return $plan instanceof CarePlan
            && $this->user()->can('update', $plan);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'frequency' => ['required', Rule::enum(TaskFrequency::class)],
            'frequency_details' => ['nullable', 'array'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'task_order' => ['nullable', 'integer', 'min:0'],
            'mandatory' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre de la tâche est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'frequency.required' => 'La fréquence est obligatoire.',
            'frequency.Illuminate\\Validation\\Rules\\Enum' => 'Fréquence invalide.',
            'duration_minutes.max' => 'La durée ne peut pas dépasser 10 heures (:max minutes).',
        ];
    }
}
