<?php

declare(strict_types=1);

namespace App\Http\Requests\Qvct;

use App\Http\Requests\BaseFormRequest;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;

class LaunchQvctCampaignRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $questionnaire = $this->route('questionnaire');

        return $questionnaire instanceof QvctQuestionnaire
            && $this->user()?->can('create', QvctCampaign::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
            'target_team' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'closes_at.after_or_equal' => 'La date de clôture doit être postérieure ou égale à la date d\'ouverture.',
        ];
    }
}
