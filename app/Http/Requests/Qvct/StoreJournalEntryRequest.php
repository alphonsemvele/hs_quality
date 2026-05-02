<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Enums\QvctMood;
use App\Http\Requests\BaseFormRequest;
use App\Models\QvctJournalEntry;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QvctJournalEntry::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'mood' => ['required', 'string', Rule::in(array_column(QvctMood::cases(), 'value'))],
            'shared_with_rh' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mood.in' => 'Humeur invalide.',
            'body.max' => 'L\'entrée du journal ne peut pas dépasser :max caractères.',
        ];
    }
}
