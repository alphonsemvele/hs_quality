<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Enums\QvctExchangeAddresseeRole;
use App\Http\Requests\BaseFormRequest;
use App\Models\QvctExchangeRequest;
use Illuminate\Validation\Rule;

class StoreExchangeRequestRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QvctExchangeRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'addressee_role' => ['required', 'string', Rule::in(array_column(QvctExchangeAddresseeRole::cases(), 'value'))],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'addressee_role.in' => 'Destinataire invalide (rh ou manager).',
        ];
    }
}
