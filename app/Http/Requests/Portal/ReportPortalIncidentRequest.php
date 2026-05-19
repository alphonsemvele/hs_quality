<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Enums\CategorieIncident;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class ReportPortalIncidentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'categorie' => ['required', 'string', Rule::enum(CategorieIncident::class)],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
