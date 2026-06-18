<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AnnualReport;

class RequestAnnualReportRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AnnualReport::class) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2024', 'max:'.now()->year],
        ];
    }
}
