<?php

declare(strict_types=1);

namespace App\Http\Requests\Audits;

use App\Enums\PacActionStatus;
use App\Http\Requests\BaseFormRequest;
use App\Models\PacAction;
use Illuminate\Validation\Rule;

class UpdatePacActionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $action = $this->route('action');

        return $action instanceof PacAction
            && $this->user()?->can('update', $action);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_column(PacActionStatus::cases(), 'value'))],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'evidence_url' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
