<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Enums\QvctFrequency;
use App\Http\Requests\BaseFormRequest;
use App\Models\QvctQuestionnaire;
use Illuminate\Validation\Rule;

class StoreQvctQuestionnaireRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QvctQuestionnaire::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'frequency' => ['required', 'string', Rule::in(array_column(QvctFrequency::cases(), 'value'))],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'questions.*.label' => ['required', 'string', 'max:500'],
            'questions.*.scale' => ['required', 'string', 'in:1-5,1-10,yes_no'],
            'questions.*.category' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.*.key.regex' => 'La clé d\'une question doit contenir uniquement des lettres minuscules, chiffres et underscores.',
            'frequency.in' => 'Cadence invalide (hebdomadaire / mensuelle / trimestrielle).',
        ];
    }
}
