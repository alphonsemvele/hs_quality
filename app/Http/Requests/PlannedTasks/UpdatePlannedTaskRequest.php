<?php

namespace App\Http\Requests\PlannedTasks;

use App\Enums\TaskFrequency;
use App\Http\Requests\BaseFormRequest;
use App\Models\PlannedTask;
use App\Support\PlannedTaskFrequencyValidator;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * `frequency` may be absent from the payload on PATCH-style updates.
     * In that case, fall back to the existing task's frequency so the
     * `frequency_details` schema validation still has the right context.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $this->all();
            if (! array_key_exists('frequency', $data)) {
                $task = $this->route('task');
                if ($task instanceof PlannedTask) {
                    $data['frequency'] = $task->frequency?->value;
                }
            }
            PlannedTaskFrequencyValidator::validate($v, $data);
        });
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
