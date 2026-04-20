<?php

namespace App\Http\Requests\PlannedTasks;

use App\Enums\TaskFrequency;
use App\Http\Requests\BaseFormRequest;
use App\Models\PlannedTask;
use Illuminate\Validation\Rule;

class UpdatePlannedTaskRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof PlannedTask
            && $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'frequency' => ['sometimes', 'required', Rule::enum(TaskFrequency::class)],
            'frequency_details' => ['nullable', 'array'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'task_order' => ['sometimes', 'integer', 'min:0'],
            'mandatory' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre de la tâche est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser :max caractères.',
            'duration_minutes.max' => 'La durée ne peut pas dépasser 10 heures (:max minutes).',
        ];
    }
}
