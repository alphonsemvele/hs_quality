<?php

declare(strict_types=1);

namespace App\Http\Requests\Beneficiaries;

use App\Http\Requests\BaseFormRequest;
use App\Models\BeneficiarySatisfactionRating;

class StoreSatisfactionRatingRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BeneficiarySatisfactionRating::class) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'rated_at' => ['required', 'date', 'before_or_equal:today'],
            'intervention_id' => ['nullable', 'uuid', 'exists:interventions,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'score.between' => 'La note doit être comprise entre 1 et 5.',
            'rated_at.before_or_equal' => "La date d'évaluation ne peut pas être dans le futur.",
        ];
    }
}
