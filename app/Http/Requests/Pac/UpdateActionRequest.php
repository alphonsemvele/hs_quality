<?php

namespace App\Http\Requests\Pac;

use App\Enums\ActionStatus;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateActionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('action')->plan);
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'responsable' => ['nullable', 'string', 'max:150'],
            'echeance' => ['nullable', 'date'],
            'statut' => ['sometimes', Rule::enum(ActionStatus::class)],
        ];
    }
}
